<?php

namespace App\Http\Controllers\User\Plan\Stripe;

use Stripe\Stripe;
use App\Models\Plan\Plan;
use Illuminate\Http\Request;
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $user = Auth::user();

        // Default to single if payment_type not provided
        $paymentType = $request->payment_type ?? 'single';
        $mode = $paymentType === 'single' ? 'payment' : 'subscription';

        Stripe::setApiKey(config('STRIPE_SECRET'));

        try {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'mode' => $mode,
                'customer_email' => $user->email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => (int)($plan->discounted_price * 100), // convert to cents
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $mode === 'subscription' ? 'Recurring Subscription Plan' : 'One-Time Purchase',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_type' => 'plan_subscription', // always send for webhook handling
                    'mode' => $mode, // optional, useful for distinguishing
                ],
                'success_url' => url('/payment/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/payment/cancel'),
            ]);

            return response()->json([
                'url' => $session->url,
                'id' => $session->id,
                'mode' => $mode,
            ]);

        } catch (\Exception $e) {
            Log::error("Stripe checkout session creation failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Unable to create Stripe checkout session'
            ], 500);
        }
    }
}
