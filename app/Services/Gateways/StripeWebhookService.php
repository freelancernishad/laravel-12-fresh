<?php

namespace App\Services\Gateways;

use App\Events\StripePaymentEvent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\StripeLog;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    public function handleWebhook($payload, $sigHeader)
    {
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
            $log->update([
                'status' => $session->payment_status, // paid, unpaid, no_payment_required
                'payment_intent_id' => $session->payment_intent,
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => $eventType, 'session_details' => $session->toArray()]),
            ]);
            Log::info("StripeLog updated for session: {$session->id}");
            
            // Dispatch generic event
            StripePaymentEvent::dispatch($eventType, $session->toArray(), 'success', $log->user_id);
        } else {
            Log::warning("StripeLog not found for session: {$session->id}");
            // Still dispatch event, but without user_id
            StripePaymentEvent::dispatch($eventType, $session->toArray(), 'success');
        }
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
