<?php

namespace App\Http\Controllers\Auth\User;

use App\Models\User;
use App\Mail\VerifyEmail;
use Illuminate\Http\Request;

use App\Mail\OtpNotification;
use Tymon\JWTAuth\Facades\JWTAuth;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\Login\AppleAuthService;
use App\Services\Login\GoogleAuthService;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthUserController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generate a JWT token for the newly created user
        try {
            $token = JWTAuth::fromUser($user, ['guard' => 'user']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Generate verification URL (if applicable)
        $verify_url = $request->verify_url ?? null; // Optional verify URL from the request

        // Notify user for email verification
        if ($verify_url) {
            Mail::to($user->email)->send(new VerifyEmail($user, $verify_url));
        }else{
            // Generate a 6-digit numeric OTP
            $otp = random_int(100000, 999999); // Generate OTP
            $user->otp = Hash::make($otp); // Store hashed OTP
            $user->otp_expires_at = now()->addMinutes(5); // Set OTP expiration time
            $user->save();

            // Notify user with the OTP
            Mail::to($user->email)->send(new OtpNotification($otp));

        }



        // Define payload data
        $payload = [
            'email' => $user->email,
            'name' => $user->name,
            'email_verified' => $user->hasVerifiedEmail(), // Check verification status
        ];

        return response()->json([
            'token' => $token,
            'user' => $payload,
        ], 201);
    }


    /**
     * Log in a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {


        if($request->access_token){
            $googleAuthService = new GoogleAuthService();
            return $googleAuthService->login($request);
        }


        if($request->identity_token){
            $appleAuthService = new AppleAuthService();
            return $appleAuthService->login($request);
        }





        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Custom payload data, including email verification status
            $payload = [
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => !is_null($user->email_verified_at), // Checks verification status
            ];

            try {
                // Generate a JWT token with custom claims
                $token = JWTAuth::fromUser($user, ['guard' => 'user']);
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token'], 500);
            }

            return response()->json([
                'token' => $token,
                'user' => $payload,
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }



    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {

        return response()->json(Auth::user());
    }

    /**
     * Log out the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
public function logout(Request $request)
{
    try {
        $token = JWTAuth::getToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided.'
            ], 401);
        }

        JWTAuth::invalidate($token);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.'
        ], 200);
    } catch (JWTException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to logout, token invalidation failed: ' . $e->getMessage()
        ], 500);
    }
}

  /**
     * Change the password of the authenticated user.
     */
    public function changePassword(Request $request)
    {
        // Validate input using Validator
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $authUser = Auth::user();
        $user = User::find($authUser->id);

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }


    /**
     * Check if a JWT token is valid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        $token = $request->bearerToken(); // Get the token from the Authorization header

        if (!$token) {
            return response()->json(['message' => 'Token not provided.'], 400);
        }

        try {
            // Authenticate the token and retrieve the authenticated user
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Token is invalid or user not found.'], 401);
            }

            $payload = [
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => $user->hasVerifiedEmail(), // Checks verification status
            ];

            return response()->json(['message' => 'Token is valid.','user'=>$payload], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token is missing or malformed.'], 401);
        }
    }

}
