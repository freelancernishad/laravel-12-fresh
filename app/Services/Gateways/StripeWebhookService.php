<?php

namespace App\Services\Gateways;

use App\Events\StripePaymentEvent;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Exception\SignatureVerificationException;
use App\Models\StripeLog;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    public function handleWebhook($payload, $sigHeader)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $endpointSecret = config('services.stripe.webhook');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe Webhook Error: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe Webhook Error: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                $this->handleCheckoutSessionCompleted($event->data->object, $event->type);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object, $event->type);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object, $event->type);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object, $event->type);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object, $event->type);
                break;
            // Add other event types as needed
            default:
                Log::info('Received unknown event type ' . $event->type);
                // Still dispatch event for unknown types so listeners can handle if needed
                 StripePaymentEvent::dispatch($event->type, $event->data->object->toArray(), 'received');
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleCheckoutSessionCompleted($session, $eventType)
    {
        $log = StripeLog::where('session_id', $session->id)->first();

        if ($log) {
            $updateData = [
                'status' => $session->payment_status, // paid, unpaid, no_payment_required
                'payment_intent_id' => $session->payment_intent,
                'subscription_id' => $session->subscription, // Save subscription ID if available
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => $eventType, 'session_details' => $session->toArray()]),
            ];

            // If this is a subscription checkout, retrieve details to set next_payment_date
            if ($session->subscription) {
                try {
                    $subscription = \Stripe\Subscription::retrieve($session->subscription);
                    
                    // Log::info("Webhook Subscription Object Dump:", $subscription->toArray()); // Commented out to reduce noise

                    $nextPaymentDate = null;

                    // Try top-level current_period_end
                    if (isset($subscription->current_period_end)) {
                         $nextPaymentDate = $subscription->current_period_end;
                    } 
                    // Fallback: Try first subscription item's current_period_end
                    elseif (isset($subscription->items->data[0]->current_period_end)) {
                         $nextPaymentDate = $subscription->items->data[0]->current_period_end;
                    }

                    $updateData['next_payment_date'] = $nextPaymentDate 
                        ? date('Y-m-d H:i:s', $nextPaymentDate) 
                        : null;

                    $updateData['next_payment_status'] = 'scheduled';
                    $updateData['product_name'] = $log->product_name; 
                } catch (\Exception $e) {
                    Log::error("Failed to retrieve subscription in webhook: " . $e->getMessage());
                }
            }

            $log->update($updateData);
            Log::info("StripeLog updated for session: {$session->id}");
            
            // Dispatch generic event
            StripePaymentEvent::dispatch($eventType, $session->toArray(), 'success', $log->user_id);
        } else {
            Log::warning("StripeLog not found for session: {$session->id}");
            // Still dispatch event, but without user_id
            StripePaymentEvent::dispatch($eventType, $session->toArray(), 'success');
        }
    }

    protected function handleInvoicePaymentSucceeded($invoice, $eventType)
    {
        // Prevent duplicate logs for the initial subscription payment (already handled by checkout.session.completed)
        if (isset($invoice->billing_reason) && $invoice->billing_reason === 'subscription_create') {
            Log::info("Skipping invoice.payment_succeeded for subscription creation to avoid duplicate log: {$invoice->id}");
            return;
        }

        // This event happens for subscription renewals
        // We need to create a NEW log entry for each renewal payment
        
        $user = \App\Models\User::where('stripe_id', $invoice->customer)->first();
        $userId = $user ? $user->id : null;

        // Extract detailed info
        $lineItem = $invoice->lines->data[0] ?? null;
        $planName = $lineItem ? ($lineItem->description ?? 'Unknown Subscription') : 'Unknown';
        $periodStart = isset($lineItem->period->start) ? date('Y-m-d H:i:s', $lineItem->period->start) : null;
        $periodEnd = isset($lineItem->period->end) ? date('Y-m-d H:i:s', $lineItem->period->end) : null; // Effectively the next charge date
        
        $interval = $lineItem->price->recurring->interval ?? null;
        $intervalCount = $lineItem->price->recurring->interval_count ?? null;

        $log = StripeLog::create([
            'user_id' => $userId,
            'type' => 'subscription_renewal', // Distinct type for renewals
            'stripe_customer_id' => $invoice->customer,
            'session_id' => $invoice->subscription, // Using subscription ID as session ref (legacy)
            'subscription_id' => $invoice->subscription, // Storing explicitly
            'payment_intent_id' => $invoice->payment_intent,
            'amount' => $invoice->amount_paid / 100,
            'currency' => $invoice->currency,
            'status' => $invoice->status, // paid
            'product_name' => $planName,
            'next_payment_date' => $periodEnd,
            'next_payment_status' => $periodEnd ? 'scheduled' : 'none',
            'interval' => $interval,
            'interval_count' => $intervalCount,
            'payload' => [
                'webhook_event' => $eventType, 
                'subscription_name' => $planName,
                'current_period_start' => $periodStart,
                'next_payment_date' => $periodEnd,
                'next_payment_status' => $periodEnd ? 'scheduled' : 'none',
                'interval' => $interval,
                'interval_count' => $intervalCount,
                'invoice_details' => $invoice->toArray()
            ],
        ]);
        
        Log::info("StripeLog created for subscription renewal: {$invoice->id}", ['next_payment' => $periodEnd]);

        StripePaymentEvent::dispatch($eventType, $invoice->toArray(), 'success', $userId);
    }
    
    protected function handleSubscriptionDeleted($subscription, $eventType)
    {
        // Find the latest log for this subscription to update status or create a new log
        // Ideally we should create a new event log for the cancellation action
        $user = \App\Models\User::where('stripe_id', $subscription->customer)->first();
        $userId = $user ? $user->id : null;

        $log = StripeLog::create([
            'user_id' => $userId,
            'type' => 'subscription_canceled',
            'stripe_customer_id' => $subscription->customer,
            'subscription_id' => $subscription->id,
            'status' => 'canceled',
            'next_payment_status' => 'none',
            'payload' => [
                'webhook_event' => $eventType,
                'cancellation_details' => $subscription->toArray()
            ],
        ]);
        
        Log::info("StripeLog created for subscription cancellation: {$subscription->id}");
        
        StripePaymentEvent::dispatch($eventType, $subscription->toArray(), 'canceled', $userId);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent, $eventType)
    {
        // Payment intents can be associated with logs via payment_intent_id
        $log = StripeLog::where('payment_intent_id', $paymentIntent->id)->first();

        if ($log) {
            $log->update([
                'status' => $paymentIntent->status, // succeeded
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => $eventType, 'intent_details' => $paymentIntent->toArray()]),
            ]);
             Log::info("StripeLog updated for payment intent: {$paymentIntent->id}");
             
             StripePaymentEvent::dispatch($eventType, $paymentIntent->toArray(), 'success', $log->user_id);
        } else {
             StripePaymentEvent::dispatch($eventType, $paymentIntent->toArray(), 'success');
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent, $eventType)
    {
        $log = StripeLog::where('payment_intent_id', $paymentIntent->id)->first();

        if ($log) {
            $log->update([
                'status' => $paymentIntent->status, // requires_payment_method, etc.
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => $eventType, 'intent_details' => $paymentIntent->toArray()]),
            ]);
            Log::info("StripeLog updated for failed payment intent: {$paymentIntent->id}");
            
            StripePaymentEvent::dispatch($eventType, $paymentIntent->toArray(), 'failed', $log->user_id);
        } else {
            StripePaymentEvent::dispatch($eventType, $paymentIntent->toArray(), 'failed');
        }
    }
}
