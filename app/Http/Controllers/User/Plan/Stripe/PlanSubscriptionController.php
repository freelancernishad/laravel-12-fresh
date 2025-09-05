<?php

namespace App\Http\Controllers\User\Plan\Stripe;

use Stripe\Stripe;
use App\Models\Plan\Plan;
use Illuminate\Http\Request;
use App\Helpers\StripeHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session as StripeSession;

class PlanSubscriptionController extends Controller
{
    /**
     * Create Stripe Checkout Session
     * Handles both one-time payments and recurring subscriptions
     */
    public function PurchasePlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'payment_type' => 'nullable|in:single,subscription',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::findOrFail($request->plan_id);

        $paymentType = $request->payment_type ?? 'single';
        $mode = $paymentType === 'single' ? 'payment' : 'subscription';

        // Prepare line items
        $lineItems = [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => (int)($plan->discounted_price * 100),
                'product_data' => [
                    'name' => $plan->name,
                    'description' => $mode === 'subscription'
                        ? 'Recurring Subscription Plan'
                        : 'One-Time Purchase',
                ],
            ],
            'quantity' => 1,
        ]];

        // Prepare dynamic metadata
        $metadata = [
            'plan_id' => $plan->id,
            'payment_type' => 'plan_subscription', // Dynamic type
            'mode' => $mode,
        ];

        try {
            $stripeHelper = new StripeHelper();
            $session = $stripeHelper->createCheckoutSession([
                'request' => $request,
                'lineItems' => $lineItems,
                'metadata' => $metadata,
                'success_url' => $request->success_url ?? url('/payment/success'),
                'cancel_url' => $request->cancel_url ?? url('/payment/cancel'),
            ]);

            return response()->json([
                'url' => $session->url,
                'id' => $session->id,
                'mode' => $mode,
            ]);
        } catch (\Exception $e) {
            Log::error("Purchase failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Unable to create Stripe checkout session'
            ], 500);
        }
    }
}
