<?php

namespace App\Services\Gateways;

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
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;
            // Add other event types as needed
            default:
                Log::info('Received unknown event type ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        $log = StripeLog::where('session_id', $session->id)->first();

        if ($log) {
            $log->update([
                'status' => $session->payment_status, // paid, unpaid, no_payment_required
                'payment_intent_id' => $session->payment_intent,
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => 'checkout.session.completed', 'session_details' => $session->toArray()]),
            ]);
            Log::info("StripeLog updated for session: {$session->id}");
        } else {
            Log::warning("StripeLog not found for session: {$session->id}");
        }
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        // Payment intents can be associated with logs via payment_intent_id
        $log = StripeLog::where('payment_intent_id', $paymentIntent->id)->first();

        if ($log) {
            $log->update([
                'status' => $paymentIntent->status, // succeeded
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => 'payment_intent.succeeded', 'intent_details' => $paymentIntent->toArray()]),
            ]);
             Log::info("StripeLog updated for payment intent: {$paymentIntent->id}");
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $log = StripeLog::where('payment_intent_id', $paymentIntent->id)->first();

        if ($log) {
            $log->update([
                'status' => $paymentIntent->status, // requires_payment_method, etc.
                'payload' => array_merge($log->payload ?? [], ['webhook_event' => 'payment_intent.payment_failed', 'intent_details' => $paymentIntent->toArray()]),
            ]);
            Log::info("StripeLog updated for failed payment intent: {$paymentIntent->id}");
        }
    }
}
