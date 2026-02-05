<?php

namespace App\Services\Login;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AppleAuthService
{
    public function login(Request $request)
    {
        try {
            $identityToken = $request->identity_token;
            $name = $request->name;

            // Decode the Apple Identity Token
            $appleUserInfo = $this->decodeAppleIdentityToken($identityToken);

            if (!$appleUserInfo || !isset($appleUserInfo['email'])) {
                return response()->json([
                    'error' => 'Invalid or expired Apple identity token.',
                ], 400);
            }

            // Check if the user already exists
            $user = User::where('email', $appleUserInfo['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name ?? explode('@', $appleUserInfo['email'])[0],
                    'email' => $appleUserInfo['email'],
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'profile_completion' => 10,
                ]);
            } else {
                $user->update(['email_verified_at' => now()]);
            }

            // Authenticate the user
            Auth::login($user);

            // Generate JWT
            try {
                $token = JWTAuth::fromUser($user, ['guard' => 'user']);
            } catch (JWTException $e) {
                return response()->json([
                    'error' => 'Could not generate JWT token for Apple login.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'profile_completion' => $user->profile_completion,
                'message' => 'Login successful via Apple',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Apple Login failed due to an internal error.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decode Apple Identity Token
     */
    private function decodeAppleIdentityToken($identityToken)
    {
        try {
            $tokenParts = explode(".", $identityToken);
            $payload = base64_decode(strtr($tokenParts[1], '-_', '+/'));
            return json_decode($payload, true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
