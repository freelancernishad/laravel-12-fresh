<?php

namespace App\Listeners;

use App\Events\EkpayPaymentEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessEkpayPayment
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
    public function handle(EkpayPaymentEvent $event)
    {
        Log::info("ProcessEkpayPayment Listener Triggered for Trnx: {$event->trnxId} - Status: {$event->status}");

        $payment = \App\Models\Payment::where('transaction_id', $event->trnxId)->with('items')->first();
        $ekpayLog = \App\Models\EkpayLog::where('trnx_id', $event->trnxId)->first();

        if (!$payment) {
            Log::error("Payment record not found for Trnx: {$event->trnxId}");
            return;
        }

        if ($event->status === 'Paid') {
            // 1. Update Master Payment Record
            $payment->update([
                'status' => 'Paid',
                'webhook_status' => 'processed',
                'webhook_received_at' => now(),
            ]);

            // 2. Update All Associated Items
            $payment->items()->update(['status' => 'Paid']);

            // 3. Update Ekpay Log
            if ($ekpayLog) {
                $ekpayLog->update(['status' => 'success']);
            }
            
            Log::info("Ekpay Payment Succeeded and records updated for Trnx: {$event->trnxId}");

        } elseif ($event->status === 'failed') {
            $payment->update(['status' => 'failed']);
            $payment->items()->update(['status' => 'failed']);
            if ($ekpayLog) {
                $ekpayLog->update(['status' => 'failed']);
            }
            Log::warning("Ekpay Payment Failed for Trnx: {$event->trnxId}");
        }
    }
}
