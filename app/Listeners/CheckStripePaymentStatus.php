<?php

namespace App\Listeners;

use App\Events\StripePaymentEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckStripePaymentStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StripePaymentEvent $event)
    {
        Log::info("CheckStripePaymentStatus Listener Triggered for Event: {$event->type}");

        // Only process for checkout session or subscription events to avoid double processing with payment_intent
        // payment_intent.succeeded often fires alongside checkout.session.completed
        if (!in_array($event->type, ['checkout.session.completed', 'invoice.payment_succeeded'])) {
             Log::info("Skipping subscription processing for event type: {$event->type}");
             return;
        }

        if ($event->status === 'success') {
            Log::info("Payment Verified via Event Listener!", [
                'user_id' => $event->userId,
                'amount' => $event->payload['amount_total'] ?? $event->payload['amount'] ?? 'N/A',
                'currency' => $event->payload['currency'] ?? 'N/A',
                // 'raw_payload' => $event->payload
            ]);

            $this->processSubscription($event);

        } elseif ($event->status === 'failed') {
            Log::warning("Payment Failed Alert via Event Listener!", [
                'user_id' => $event->userId,
                'reason' => $event->payload['last_payment_error']['message'] ?? 'Unknown error'
            ]);
        }
    }

    protected function processSubscription(StripePaymentEvent $event)
    {
        if (!$event->userId) return;

        $user = \App\Models\User::find($event->userId);
        if (!$user) return;

        // Find Plan ID from metadata or StripeLog
        $planId = $event->payload['metadata']['plan_id'] ?? null;
        
        if (!$planId) {
            $sessionId = $event->payload['id'] ?? null;
            if ($sessionId) {
                $log = \App\Models\StripeLog::where('session_id', $sessionId)->first();
                $planId = $log?->plan_id;
            }
        }

        if (!$planId) {
            Log::warning("Plan ID not found for successful payment. User: {$user->id}, Session: " . ($event->payload['id'] ?? 'N/A'));
            return;
        }

        $plan = \App\Models\Plan\Plan::find($planId);
        if (!$plan) return;

        // Create or update subscription
        // For simplicity, we create a new active subscription. 
        // In a real app, you might want to cancel old ones.
        
        $subscriptionId = $event->payload['subscription'] ?? null;
        
        $startDate = now();
        $endDate = null;

        // Calculate end date for one-time payments if duration is set
        if (!$subscriptionId && $plan->duration) {
             $numericDuration = (int) preg_replace('/[^0-9]/', '', $plan->duration);
             if ($numericDuration > 0) {
                 $endDate = now()->addMonths($numericDuration);
             }
        }

        if (!$subscriptionId) {
            // One-time payment: search for an existing record by session_id in a different way or just create
            // Actually, we can use user_id + plan_id + status=active as a weak unique key, 
            // but for one-time, better to check if session was already processed.
            // Since we don't have checkout_session_id in plan_subscriptions, we'll use user_id/plan_id.
            
            $existing = \App\Models\Plan\PlanSubscription::where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->first();
                
            if ($existing) {
                Log::info("PlanSubscription already exists for user {$user->id} (One-time). Skipping duplicate.");
                return;
            }
        }

        \App\Models\Plan\PlanSubscription::updateOrCreate(
            [
                'stripe_subscription_id' => $subscriptionId,
                'user_id' => $subscriptionId ? $user->id : null, // If one-time, we matched above
            ],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'original_amount' => $plan->original_price,
                'final_amount' => $plan->discounted_price,
                'discount_amount' => ($plan->original_price - $plan->discounted_price),
                'discount_percent' => $plan->discount_percentage,
                'plan_features' => $plan->features, // Array
                'billing_interval' => $subscriptionId ? ($plan->duration_type === 'year' ? 'yearly' : 'monthly') : null,
            ]
        );

        Log::info("PlanSubscription created successfully for user {$user->id}, Plan: {$plan->id}");
    }
}
