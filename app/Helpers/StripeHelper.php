<?php
namespace App\Helpers;

use Stripe\Stripe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session as StripeSession;

class StripeHelper
{

    /**
     * Create a Stripe checkout session dynamically
     *
     * @param array $params {
     *      @type \App\Models\User $user User model instance
     *      @type array $lineItems Line items for purchase
     *      @type string $mode Payment mode ('payment' or 'subscription')
     *      @type array $metadata Additional metadata
     *      @type string $successUrl Success URL
     *      @type string $cancelUrl Cancel URL
     *      @type string $currency Currency code (default: USD)
     *      @type array $paymentMethodTypes Payment methods (default: ['card'])
     * }
     * @return \Stripe\Checkout\Session
     * @throws \Exception
     */

    function createCheckoutSession(array $params): StripeSession
    {

        $user = Auth::user();

        $request = $params['request'];
        $paymentType = $request->payment_type ?? 'single';
        $mode = $paymentType === 'single' ? 'payment' : 'subscription';

        // Set Stripe API key
        \Stripe\Stripe::setApiKey(config('STRIPE_SECRET'));

        // Set default values
        $defaults = [
            'currency' => 'usd',
            'paymentMethodTypes' => ['card'],
        ];

        $params = array_merge($defaults, $params);

        // Validate required parameters
        $required = ['request','lineItems', 'metadata', 'success_url', 'cancel_url'];
        foreach ($required as $key) {
            if (empty($params[$key])) {
                throw new \InvalidArgumentException("Missing required parameter: {$key}");
            }
        }

        $metadata = array_merge(
                $params['metadata'],
                ['user_id' => $user->id]
            );
        // Prepare session parameters
        $sessionParams = [
            'payment_method_types' => $params['paymentMethodTypes'],
            'mode' => $mode,
            'customer_email' => $user->email,
            'line_items' => $params['lineItems'],
            'metadata' => $metadata,
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
        ];

        // Create Stripe session
        try {
            return StripeSession::create($sessionParams);
        } catch (\Exception $e) {
            Log::error("Stripe checkout session creation failed: " . $e->getMessage());
            throw $e;
        }
    }
}
