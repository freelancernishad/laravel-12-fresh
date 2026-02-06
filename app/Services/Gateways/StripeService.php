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
     /** Create a Checkout Session for one-time payments.
     */
    public function createCheckoutSession(User $user, array $items, string $successUrl, string $cancelUrl, bool $saveCard = false)
    {
        $customer = $this->createOrGetCustomer($user);

        $params = [
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => $items,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        if ($saveCard) {
            $params['payment_intent_data'] = [
                'setup_future_usage' => 'off_session', 
            ];
        }

        $session = CheckoutSession::create($params);

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
            'subscription_id' => $session->subscription,
            'amount' => $session->amount_total ? $session->amount_total : 0,
            'currency' => $session->currency,
            'status' => $session->status,
            'interval' => 'month', // Default assumption for fixed price if not fetched
            'interval_count' => 1,
            'payload' => $session->toArray(),
        ]);

        return $session;
    }

    /**
     * Create a subscription session with dynamic price (custom interval).
     * 
     * @param array $priceData ['amount', 'currency', 'interval', 'interval_count', 'product_name', 'duration_in_months']
     */
    public function createCustomSubscriptionSession(User $user, array $priceData, string $successUrl, string $cancelUrl)
    {
        $customer = $this->createOrGetCustomer($user);

        $subscriptionData = [];

        // Calculate cancel_at if duration is provided (e.g., charge for 8 months)
        if (isset($priceData['duration_in_months']) && $priceData['duration_in_months'] > 0) {
             // Calculate end timestamp based on interval and count * duration
             // Logic: If interval is month, count is 1, duration is 8 -> 8 months later
             // Note: This is an approximation. Precise billing cycles are handled by Stripe.
             // We set cancel_at to: now + (interval_count * duration_in_months)
             
             $now = \Carbon\Carbon::now();
             $interval = $priceData['interval']; // day, week, month, year
             $count = $priceData['interval_count'] ?? 1;
             $totalDuration = $count * $priceData['duration_in_months'];
             
             if ($interval === 'day') $now->addDays($totalDuration);
             elseif ($interval === 'week') $now->addWeeks($totalDuration);
             elseif ($interval === 'month') $now->addMonths($totalDuration);
             elseif ($interval === 'year') $now->addYears($totalDuration);
             
             $subscriptionData['cancel_at'] = $now->timestamp;
        }

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
            'subscription_data' => $subscriptionData,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        \App\Models\StripeLog::create([
            'user_id' => $user->id,
            'type' => 'subscription_custom',
            'stripe_customer_id' => $customer->id,
            'session_id' => $session->id,
            'subscription_id' => $session->subscription,
            'amount' => $priceData['amount'] / 100,
            'currency' => $priceData['currency'] ?? 'usd',
            'status' => $session->status,
            'product_name' => $priceData['product_name'] ?? 'Subscription Plan',
            'interval' => $priceData['interval'],
            'interval_count' => $priceData['interval_count'] ?? 1,
            'next_payment_status' => 'scheduled', // Initial status
            'payload' => $session->toArray(),
        ]);

        return $session;
    }

    /**
     * Cancel a subcription immediately.
     */
    public function cancelSubscription(string $subscriptionId)
    {
        $subscription = \Stripe\Subscription::retrieve($subscriptionId);
        return $subscription->cancel();
    }

    /**
     * Create a Payment Intent.
     */
    public function createPaymentIntent(User $user, int $amount, string $currency = 'usd', array $metadata = [], bool $setupFutureUsage = false)
    {
        $customer = $this->createOrGetCustomer($user);

        $params = [
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customer->id,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ];

        if ($setupFutureUsage) {
            $params['setup_future_usage'] = 'off_session';
        }

        $intent = PaymentIntent::create($params);

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
     * Update an existing Payment Intent.
     */
    public function updatePaymentIntent(string $paymentIntentId, array $data)
    {
        return PaymentIntent::update($paymentIntentId, $data);
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

        if (!$paymentMethod) {
            return null;
        }

        $details = [
            'type' => $paymentMethod->type,
            'name' => $paymentMethod->billing_details->name,
            'email' => $paymentMethod->billing_details->email,
            'phone' => $paymentMethod->billing_details->phone,
            'address' => $paymentMethod->billing_details->address ? $paymentMethod->billing_details->address->toArray() : null,
        ];

        if ($paymentMethod->type === 'card') {
            $details = array_merge($details, [
                'brand' => $paymentMethod->card->brand,
                'last4' => $paymentMethod->card->last4,
                'exp_month' => $paymentMethod->card->exp_month,
                'exp_year' => $paymentMethod->card->exp_year,
                'country' => $paymentMethod->card->country,
                'funding' => $paymentMethod->card->funding, // credit, debit, prepaid
                'wallet' => $paymentMethod->card->wallet ? $paymentMethod->card->wallet->type : null, // apple_pay, google_pay
            ]);
        } elseif ($paymentMethod->type === 'us_bank_account') {
            $details = array_merge($details, [
                'bank_name' => $paymentMethod->us_bank_account->bank_name,
                'last4' => $paymentMethod->us_bank_account->last4,
                'account_holder_type' => $paymentMethod->us_bank_account->account_holder_type, // individual, company
                'routing_number' => $paymentMethod->us_bank_account->routing_number,
            ]);
        } elseif ($paymentMethod->type === 'sepa_debit') {
            $details = array_merge($details, [
                'bank_code' => $paymentMethod->sepa_debit->bank_code,
                'last4' => $paymentMethod->sepa_debit->last4,
                'country' => $paymentMethod->sepa_debit->country,
            ]);
        }
        
        // Add more types as needed (alipay, paypal, etc.)

        return $details;
    }
}
