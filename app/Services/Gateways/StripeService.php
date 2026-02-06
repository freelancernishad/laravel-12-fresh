<?php

namespace App\Services\Gateways;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\PaymentMethod;
use Stripe\Checkout\Session as CheckoutSession;
use App\Models\User;
use Exception;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create or retrieve a Stripe customer for a user.
     */
    public function createOrGetCustomer(User $user)
    {
        if ($user->stripe_id) {
            try {
                return Customer::retrieve($user->stripe_id);
            } catch (Exception $e) {
                // If retrieval fails (e.g., customer deleted in Stripe), create a new one
            }
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_id' => $customer->id]);

        return $customer;
    }

    /**
     * Create a Checkout Session for one-time payments.
     */
    public function createCheckoutSession(User $user, array $items, string $successUrl, string $cancelUrl)
    {
        $customer = $this->createOrGetCustomer($user);

        $session = CheckoutSession::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => $items,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        \App\Models\StripeLog::create([
            'user_id' => $user->id,
            'type' => 'checkout',
            'stripe_customer_id' => $customer->id,
            'session_id' => $session->id,
            'payment_intent_id' => $session->payment_intent,
            'amount' => $session->amount_total ? $session->amount_total / 100 : 0,
            'currency' => $session->currency,
            'status' => $session->payment_status,
            'payload' => $session->toArray(),
        ]);

        return $session;
    }

    /**
     * Create a Checkout Session for subscriptions.
     */
    public function createSubscriptionSession(User $user, string $priceId, string $successUrl, string $cancelUrl)
    {
        $customer = $this->createOrGetCustomer($user);

        $session = CheckoutSession::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        \App\Models\StripeLog::create([
            'user_id' => $user->id,
            'type' => 'subscription',
            'stripe_customer_id' => $customer->id,
            'session_id' => $session->id,
            'amount' => $session->amount_total ? $session->amount_total : 0,
            'currency' => $session->currency,
            'status' => $session->status,
            'payload' => $session->toArray(),
        ]);

        return $session;
    }

    /**
     * Create a subscription session with dynamic price (custom interval).
     */
    public function createCustomSubscriptionSession(User $user, array $priceData, string $successUrl, string $cancelUrl)
    {
        $customer = $this->createOrGetCustomer($user);

        // Price data should include: amount, currency, interval, interval_count, product_name
        $session = CheckoutSession::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $priceData['currency'] ?? 'usd',
                    'product_data' => [
                        'name' => $priceData['product_name'] ?? 'Subscription Plan',
                    ],
                    'unit_amount' => $priceData['amount'],
                    'recurring' => [
                        'interval' => $priceData['interval'], // day, week, month, year
                        'interval_count' => $priceData['interval_count'] ?? 1, // count if interval=day and interval_count=2 so every 2 day charge 
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        \App\Models\StripeLog::create([
            'user_id' => $user->id,
            'type' => 'subscription_custom',
            'stripe_customer_id' => $customer->id,
            'session_id' => $session->id,
            'amount' => $priceData['amount'] / 100,
            'currency' => $priceData['currency'] ?? 'usd',
            'status' => $session->status,
            'payload' => $session->toArray(),
        ]);

        return $session;
    }

    /**
     * Create a Payment Intent.
     */
    public function createPaymentIntent(User $user, int $amount, string $currency = 'usd', array $metadata = [])
    {
        $customer = $this->createOrGetCustomer($user);

        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customer->id,
            'metadata' => $metadata,
        ]);

        \App\Models\StripeLog::create([
            'user_id' => $user->id,
            'type' => 'payment_intent',
            'stripe_customer_id' => $customer->id,
            'payment_intent_id' => $intent->id,
            'amount' => $amount / 100,
            'currency' => $currency,
            'status' => $intent->status,
            'payload' => $intent->toArray(),
        ]);

        return $intent;
    }

    /**
     * Create a Setup Intent for saving a card.
     */
    public function createSetupIntent(User $user)
    {
        $customer = $this->createOrGetCustomer($user);

        $intent = SetupIntent::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
        ]);

        \App\Models\StripeLog::create([
            'user_id' => $user->id,
            'type' => 'setup_intent',
            'stripe_customer_id' => $customer->id,
            'payment_intent_id' => $intent->id,
            'status' => $intent->status,
            'payload' => $intent->toArray(),
        ]);

        return $intent;
    }

    /**
     * List saved cards for a user.
     */
    public function listCards(User $user)
    {
        if (!$user->stripe_id) {
            return [];
        }

        return PaymentMethod::all([
            'customer' => $user->stripe_id,
            'type' => 'card',
        ]);
    }

    /**
     * Delete a saved card.
     */
    public function deleteCard(string $paymentMethodId)
    {
        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        return $paymentMethod->detach();
    }

    /**
     * Retrieve payment details from a Checkout Session.
     */
    public function retrieveSessionPaymentDetails(string $sessionId)
    {
        $session = CheckoutSession::retrieve([
            'id' => $sessionId,
            'expand' => ['payment_intent.payment_method', 'subscription.default_payment_method', 'setup_intent.payment_method'],
        ]);

        $paymentMethod = null;

        if ($session->payment_intent && $session->payment_intent->payment_method) {
            $paymentMethod = $session->payment_intent->payment_method;
        } elseif ($session->subscription && $session->subscription->default_payment_method) {
            $paymentMethod = $session->subscription->default_payment_method;
        } elseif ($session->setup_intent && $session->setup_intent->payment_method) {
            $paymentMethod = $session->setup_intent->payment_method;
        }

        if ($paymentMethod && $paymentMethod->type === 'card') {
            return [
                'type' => 'card',
                'brand' => $paymentMethod->card->brand,
                'last4' => $paymentMethod->card->last4,
                'exp_month' => $paymentMethod->card->exp_month,
                'exp_year' => $paymentMethod->card->exp_year,
                'card_holder' => $paymentMethod->billing_details->name,
                'country' => $paymentMethod->card->country,
                'funding' => $paymentMethod->card->funding, // credit, debit, prepaid
            ];
        }

        return null;
    }
}
