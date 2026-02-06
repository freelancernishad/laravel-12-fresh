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

        if ($event->status === 'success') {
            Log::info("Payment Verified via Event Listener!", [
                'user_id' => $event->userId,
                'amount' => $event->payload['amount_total'] ?? $event->payload['amount'] ?? 'N/A',
                'currency' => $event->payload['currency'] ?? 'N/A',
                'raw_payload' => $event->payload
            ]);

            // TODO: Add your custom logic here (e.g., update user balance, activate premium, etc.)
            // if ($event->userId) {
            //     $user = \App\Models\User::find($event->userId);
            //     $user->update(['is_premium' => true]);
            // }

        } elseif ($event->status === 'failed') {
            Log::warning("Payment Failed Alert via Event Listener!", [
                'user_id' => $event->userId,
                'reason' => $event->payload['last_payment_error']['message'] ?? 'Unknown error'
            ]);
        }
    }
}
