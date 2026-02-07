<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $systemInfo = [
            'client_ip' => $request->ip(),
            'server_ip' => gethostbyname(gethostname()),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'database_connection' => \DB::connection()->getDatabaseName() ? 'Connected' : 'Disconnected',
        ];

        // Attempt to get outbound IP
        try {
            $outboundIp = file_get_contents('https://api.ipify.org', false, stream_context_create([
                'http' => ['timeout' => 5]
            ]));
            $systemInfo['outbound_ip'] = $outboundIp ?: 'Unable to detect';
        } catch (\Exception $e) {
            $systemInfo['outbound_ip'] = 'Timeout / Error';
        }

        return view('admin.dashboard', compact('systemInfo'));
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function stripeInfo()
    {
        $webhookUrl = url('/api/payment/stripe/webhook');
        $events = [
            'checkout.session.completed' => 'Occurs when a Checkout Session has been successfully completed.',
            'checkout.session.async_payment_succeeded' => 'Sent when an asynchronous payment (like bank transfer) succeeds.',
            'payment_intent.succeeded' => 'Fires when a payment attempt is successfully confirmed.',
            'payment_intent.payment_failed' => 'Fires when a payment attempt fails or is canceled.',
            'customer.subscription.created' => 'Occurs when a new subscription is started for a customer.',
            'customer.subscription.updated' => 'Sent when a subscription is changed (e.g., upgraded/downgraded).',
            'customer.subscription.deleted' => 'Sent when a subscription is canceled or expires.',
            'invoice.payment_succeeded' => 'Fires when an invoice (including renewals) is successfully paid.',
        ];

        return view('admin.stripe.webhook', compact('webhookUrl', 'events'));
    }

}
