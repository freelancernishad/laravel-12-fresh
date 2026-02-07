<?php

namespace App\Http\Controllers\User\Plan\Stripe;

use Stripe\Stripe;
use App\Models\Plan\Plan;
use Illuminate\Http\Request;
use App\Helpers\StripeHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan\PlanSubscription;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session as StripeSession;
use App\Http\Requests\User\Plan\Stripe\PurchasePlanRequest;

class PlanSubscriptionController extends Controller
{
    public function PurchasePlan(PurchasePlanRequest $request, \App\Services\Gateways\StripeService $stripeService)
    {
        $plan = Plan::findOrFail($request->plan_id);

        $paymentType = $request->payment_type ?? 'single'; // single or recurring
        $successUrl = $request->success_url ?? url('/payment/success');
        $cancelUrl = $request->cancel_url ?? url('/payment/cancel');

        try {
            if ($paymentType === 'single') {
                $items = [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => (int)($plan->discounted_price * 100),
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => 'One-Time Purchase',
                        ],
                    ],
                    'quantity' => 1,
                ]];
                
                $session = $stripeService->createCheckoutSession(
                    Auth::user(),
                    $items,
                    $successUrl,
                    $cancelUrl,
                    $request->boolean('save_card', false)
                );
            } else {
                // Subscription mode
                $priceData = [
                    'amount' => (int)($plan->discounted_price * 100),
                    'currency' => 'usd',
                    'interval' => $plan->duration_type === 'year' ? 'year' : 'month',
                    'interval_count' => (int)$plan->duration ?: 1,
                    'product_name' => $plan->name,
                ];
                
                $session = $stripeService->createCustomSubscriptionSession(
                    Auth::user(),
                    $priceData,
                    $successUrl,
                    $cancelUrl
                );
            }

            return response()->json([
                'url' => $session->url,
                'id' => $session->id,
                'mode' => $paymentType === 'single' ? 'payment' : 'subscription',
            ]);
        } catch (\Exception $e) {
            Log::error("Purchase failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Unable to create Stripe checkout session: ' . $e->getMessage()
            ], 500);
        }
    }


     /**
     * Cancel a subscription
     *
     * @param Request $request
     * @param int $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription(Request $request, int $subscriptionId)
    {
        try {
            $subscription = PlanSubscription::findOrFail($subscriptionId);

            // Verify ownership
            if ($subscription->user_id != Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $cancelImmediately = $request->input('immediately', false);
            $result = $subscription->cancelSubscription($cancelImmediately);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'status' => $result['status'],
                'end_date' => $subscription->end_date,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }



}
