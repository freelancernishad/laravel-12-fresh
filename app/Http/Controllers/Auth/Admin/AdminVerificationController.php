<?php

namespace App\Http\Controllers\Auth\Admin;

use App\Models\Admin;
use App\Mail\VerifyEmail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\OtpNotification;
use Illuminate\Routing\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Mail\RegistrationSuccessful;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdminVerificationController extends Controller
{
    public function verifyEmail(Request $request, $hash)
    {
        $admin = Admin::where('email_verification_hash', $hash)->first();

        if (!$admin) {
            return response()->json(['error' => 'Invalid or expired verification link.'], 400);
        }

        if ($admin->hasVerifiedEmail()) {
            $token = JWTAuth::fromUser($admin);

            return response()->json([
                'message' => 'Email already verified.',
                'admin' => [
                    'email' => $admin->email,
                    'name' => $admin->name,
                    'username' => $admin->username ?? null,
                    'email_verified' => true,
                ],
                'token' => $token
            ], 200);
        }

        $admin->markEmailAsVerified();

        $token = JWTAuth::fromUser($admin);

        return response()->json([
            'message' => 'Email verified successfully.',
            'admin' => [
                'email' => $admin->email,
                'name' => $admin->name,
                'username' => $admin->username ?? null,
                'email_verified' => true,
            ],
            'token' => $token
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }


        $authAdmin = Auth::guard('admin')->user();
        if (!$authAdmin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $admin = Admin::find($authAdmin->id);

        if ($admin->otp_expires_at < now()) {
            return response()->json(['error' => 'OTP has expired'], 400);
        }

        if (Hash::check($request->otp, $admin->otp)) {

            if ($admin->hasVerifiedEmail()) {
                $token = JWTAuth::fromUser($admin);

                return response()->json([
                    'message' => 'Email already verified.',
                    'admin' => [
                        'email' => $admin->email,
                        'name' => $admin->name,
                        'username' => $admin->username ?? null,
                        'email_verified' => true,
                    ],
                    'token' => $token
                ], 200);
            }

            $admin->markEmailAsVerified();
            $admin->otp = null;
            $admin->otp_expires_at = null;
            $admin->save();

            $token = JWTAuth::fromUser($admin);

            // Optional: Mail::to($admin->email)->send(new RegistrationSuccessful(['name' => $admin->name]));

            return response()->json([
                'message' => 'Email verified successfully.',
                'admin' => [
                    'email' => $admin->email,
                    'name' => $admin->name,
                    'username' => $admin->username ?? null,
                    'email_verified' => true,
                ],
                'token' => $token
            ], 200);
        }

        return response()->json(['error' => 'Invalid OTP'], 400);
    }

    public function resendVerificationLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:admins,email',
            'verify_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || $admin->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is either already verified or admin does not exist.'], 400);
        }

        $verificationToken = Str::random(60);
        $admin->email_verification_hash = $verificationToken;
        $admin->save();

        $verify_url = $request->verify_url;
        Mail::to($admin->email)->send(new VerifyEmail($admin, $verify_url));

        return response()->json(['message' => 'Verification link has been sent.'], 200);
    }

    public function resendOtp(Request $request)
    {

        $authAdmin = Auth::guard('admin')->user();
        if (!$authAdmin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $admin = Admin::find($authAdmin->id);
        if (!$admin || $admin->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is either already verified or admin does not exist.'], 400);
        }
        $otp = random_int(100000, 999999);
        $admin->otp = Hash::make($otp);
        $admin->otp_expires_at = now()->addMinutes(5);
        $admin->save();
        Mail::to($admin->email)->send(new OtpNotification($otp));

        return response()->json(['message' => 'A new OTP has been sent to your email.'], 200);
    }


}
