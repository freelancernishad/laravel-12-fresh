<?php

namespace App\Http\Controllers\Gateways;

use App\Http\Controllers\Controller;
use App\Services\Gateways\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a Checkout Session.
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $items = $request->input('items', []); // Format: [['price_data' => [...], 'quantity' => 1]]
        $successUrl = $request->input('success_url', url('/success'));
        $cancelUrl = $request->input('cancel_url', url('/cancel'));

        try {
            $session = $this->stripeService->createCheckoutSession($user, $items, $successUrl, $cancelUrl);
            return response()->json(['id' => $session->id, 'url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Create a Subscription Session.
     */
    public function subscribe(Request $request)
    {
        $user = Auth::user();
        $priceId = $request->input('price_id');
        $successUrl = $request->input('success_url', url('/success'));
        $cancelUrl = $request->input('cancel_url', url('/cancel'));

        try {
            if ($priceId) {
                $session = $this->stripeService->createSubscriptionSession($user, $priceId, $successUrl, $cancelUrl);
            } else {
                // If no price_id, use custom dynamic price parameters
                $priceData = [
                    'amount' => $request->input('amount'),
                    'currency' => $request->input('currency', 'usd'),
                    'interval' => $request->input('interval', 'month'), // day, week, month, year
                    'interval_count' => $request->input('interval_count', 1),
                    'product_name' => $request->input('product_name', 'Subscription Plan'),
                    'duration_in_months' => $request->input('duration_in_months'), // e.g. 8 for 8 months
                ];
                $session = $this->stripeService->createCustomSubscriptionSession($user, $priceData, $successUrl, $cancelUrl);
            }
            return response()->json(['id' => $session->id, 'url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel a Subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $subscriptionId = $request->input('subscription_id');

        try {
            $this->stripeService->cancelSubscription($subscriptionId);
            return response()->json(['message' => 'Subscription canceled successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Create a Payment Intent.
     */
    public function paymentIntent(Request $request)
    {
        $user = Auth::user();
        $amount = $request->input('amount');
        $currency = $request->input('currency', 'usd');
        $metadata = $request->input('metadata', []);

        try {
            $intent = $this->stripeService->createPaymentIntent($user, $amount, $currency, $metadata);
            return response()->json(['client_secret' => $intent->client_secret]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Save a Card (Setup Intent).
     */
    public function saveCard(Request $request)
    {
        $user = Auth::user();

        try {
            $intent = $this->stripeService->createSetupIntent($user);
            return response()->json(['client_secret' => $intent->client_secret]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * List user's saved cards.
     */
    public function getCards()
    {
        $user = Auth::user();

        try {
            $cards = $this->stripeService->listCards($user);
            return response()->json(['data' => $cards]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a saved card.
     */
    public function deleteCard($id)
    {
        try {
            $this->stripeService->deleteCard($id);
            return response()->json(['message' => 'Card deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get payment details from a session ID.
     */
    public function getPaymentDetails($sessionId)
    {
        try {
            $details = $this->stripeService->retrieveSessionPaymentDetails($sessionId);
            return response()->json(['data' => $details]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
