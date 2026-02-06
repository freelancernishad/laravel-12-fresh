<?php

namespace App\Http\Controllers\Gateways;

use App\Http\Controllers\Controller;
use App\Services\Gateways\StripeWebhookService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    protected $webhookService;

    public function __construct(StripeWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming Stripe webhook.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        return $this->webhookService->handleWebhook($payload, $sigHeader);
    }
}
