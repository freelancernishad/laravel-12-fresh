<?php

namespace App\Http\Controllers\Gateways;

use App\Http\Controllers\Controller;
use App\Services\Gateways\EkpayService;
use App\Services\Gateways\EkpayWebhookService;
use App\Models\EkpayLog;
use App\Models\Payment;
use App\Models\PaymentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EkpayController extends Controller
{
    protected $ekpayService;
    protected $webhookService;

    public function __construct(EkpayService $ekpayService, EkpayWebhookService $webhookService)
    {
        $this->ekpayService = $ekpayService;
        $this->webhookService = $webhookService;
    }

    /**
     * Initiate payment and get redirection URL
     */
    public function initiate(Request $request)
    {
        $user = Auth::user();
        $amount = $request->input('amount');
        $payable_id = $request->input('payable_id');
        $payable_type = $request->input('payable_type'); 
        
        $trnx_id = 'EKP' . time() . rand(100, 999);

        $trns_info = [
            "ord_det" => "Payment for " . class_basename($payable_type),
            "ord_id" => (string)$payable_id,
            "trnx_amt" => $amount,
            "trnx_currency" => "BDT",
            "trnx_id" => $trnx_id
        ];

        // Handle Guest users
        $cust_info = [
            "cust_email" => $user->email ?? $request->input('email', ""),
            "cust_id" => (string)($user->id ?? $request->input('student_id', "guest_" . time())),
            "cust_mail_addr" => $user->address ?? $request->input('address', "N/A"),
            "cust_mobo_no" => $user->phone ?? $request->input('phone', ""),
            "cust_name" => $user->name ?? $request->input('name', "Guest")
        ];

        try {
            // Create Ekpay Log
            EkpayLog::create([
                'user_id' => $user->id ?? null,
                'trnx_id' => $trnx_id,
                'amount' => $amount,
                'status' => 'pending',
                'request_payload' => [
                    'trns_info' => $trns_info,
                    'cust_info' => $cust_info,
                    'meta' => $request->input('meta', [])
                ],
                'redirect_urls' => [
                    'success' => $request->input('s_url'),
                    'fail' => $request->input('f_url'),
                    'cancel' => $request->input('c_url'),
                ]
            ]);

            $meta = $request->input('meta', []);
            $fees = $meta['fees'] ?? [];

            // 1. Create Master Payment record
            $payment = Payment::create([
                'user_id' => $user->id ?? null,
                'payable_id' => $payable_id,
                'payable_type' => $payable_type,
                'amount' => $amount,
                'currency' => 'BDT',
                'payment_method' => 'Ekpay',
                'transaction_id' => $trnx_id,
                'status' => 'pending',
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'meta' => $meta,
            ]);

            // 2. Create individual Payment Items
            if (!empty($fees)) {
                foreach ($fees as $fee) {
                    $originalHead = $fee['head'] ?? 'N/A';
                    $feeId = $fee['id'] ?? 'N/A';
                    $feeMonth = null;
                    
                    if (preg_match('/\((.*?)\)/', $originalHead, $matches)) {
                        $feeMonth = $matches[1];
                    }

                    $cleanHead = trim(preg_replace('/\s*\(.*?\)\s*/', '', $originalHead));
                    $feeType = strtolower(str_replace(' ', '_', $cleanHead));
                    $feeYear = date('Y');

                    PaymentItem::create([
                        'payment_id' => $payment->id,
                        'fee_master_id' => $feeId,
                        'fee_head' => $cleanHead,
                        'fee_type' => $feeType,
                        'amount' => $fee['amount'],
                        'fee_month' => $feeMonth,
                        'fee_year' => $feeYear,
                        'status' => 'pending',
                        'date' => now()->format('Y-m-d'),
                        'time' => now()->format('H:i:s'),
                    ]);
                }
            }

            $redirectUrls = [
                'success' => $request->input('s_url'),
                'fail' => $request->input('f_url'),
                'cancel' => $request->input('c_url'),
            ];

            $result = $this->ekpayService->initiatePayment($trns_info, $cust_info, $redirectUrls);

            // Update log with secure token
            EkpayLog::where('trnx_id', $trnx_id)->update([
                'secure_token' => $result['secure_token'],
                'response_payload' => $result
            ]);

            return response()->json([
                'status' => 'success',
                'url' => $result['payment_url'],
                'trnx_id' => $trnx_id
            ]);

        } catch (\Exception $e) {
            Log::error('Ekpay Initiation Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Webhook (IPN) handler
     */
    public function webhook(Request $request)
    {
        return $this->webhookService->handleWebhook($request->all());
    }

    public function success(Request $request)
    {
        $trnx_id = $request->input('trnx_id');
        $log = EkpayLog::where('trnx_id', $trnx_id)->first();
        
        if ($log && isset($log->redirect_urls['success'])) {
            $url = $log->redirect_urls['success'];
            $separator = str_contains($url, '?') ? '&' : '?';
            return redirect()->to($url . $separator . 'trnx_id=' . $trnx_id . '&status=success');
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect()->away("{$frontendUrl}/payment/success?trnx_id={$trnx_id}&gateway=ekpay");
    }

    public function fail(Request $request)
    {
        $trnx_id = $request->input('trnx_id');
        $log = EkpayLog::where('trnx_id', $trnx_id)->first();

        if ($log && isset($log->redirect_urls['fail'])) {
            $url = $log->redirect_urls['fail'];
            $separator = str_contains($url, '?') ? '&' : '?';
            return redirect()->to($url . $separator . 'trnx_id=' . $trnx_id . '&status=fail');
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect()->away("{$frontendUrl}/payment/fail?trnx_id={$trnx_id}&gateway=ekpay");
    }

    public function cancel(Request $request)
    {
        $trnx_id = $request->input('trnx_id');
        $log = EkpayLog::where('trnx_id', $trnx_id)->first();

        if ($log && isset($log->redirect_urls['cancel'])) {
            $url = $log->redirect_urls['cancel'];
            $separator = str_contains($url, '?') ? '&' : '?';
            return redirect()->to($url . $separator . 'trnx_id=' . $trnx_id . '&status=cancel');
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect()->away("{$frontendUrl}/payment/cancel?trnx_id={$trnx_id}&gateway=ekpay");
    }

    /**
     * Manual Status Check
     */
    public function checkStatus(Request $request)
    {
        $trnx_id = $request->input('trnx_id');
        
        $payment = Payment::where('transaction_id', $trnx_id)->first();
        
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment record not found'], 404);
        }

        $log = EkpayLog::where('trnx_id', $trnx_id)->first();
        if (!$log) {
            return response()->json(['status' => 'error', 'message' => 'Payment log not found'], 404);
        }

        $trans_date = $log->created_at->format('Y-m-d');
        $gateway_result = $this->ekpayService->checkStatus($trnx_id, $trans_date);

        $success_codes = ['1000', '1020'];
        $gateway_msg_code = $gateway_result['msg_code'] ?? null;

        if ($payment->status !== 'Paid' && in_array($gateway_msg_code, $success_codes)) {
            event(new \App\Events\EkpayPaymentEvent($trnx_id, $gateway_result, 'Paid', $payment->user_id));
            $payment->refresh();
        }

        return response()->json([
            'status' => $payment->status === 'Paid' ? 'success' : 'pending',
            'message' => $payment->status === 'Paid' ? 'Payment is successful' : 'Payment is still pending',
            'local_status' => $payment->status,
            'gateway_status' => $gateway_result['msg_code'] ?? 'unknown',
            'gateway_response' => $gateway_result
        ]);
    }
}
