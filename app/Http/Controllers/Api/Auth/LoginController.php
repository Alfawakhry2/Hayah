<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('phone', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'Invalid Credentials',
                ], 401);
            }

            $user = User::where('phone', $request->phone)->first();

            // Check if phone is verified
            if (!$user->phone_verified_at) {
                // Resend OTP for verification
                $otp = OtpService::sendOtp($user->phone);

                // Invalidate the temporary token
                JWTAuth::invalidate($token);

                return response()->json([
                    'message' => 'Phone not verified. OTP sent for verification.',
                    'register_token' => $user->register_token,
                    'next_endpoint' => 'api/register/verify-phone'
                ], 403);
            }

            // Check if registration is complete
            if (!$user->is_complete) {
                // Invalidate the temporary token
                JWTAuth::invalidate($token);

                return response()->json([
                    'message' => 'Registration not complete',
                    'register_token' => $user->register_token,
                    'next_endpoint' => $this->getNextStepEndpoint($user)
                ], 403);
            }

            // Create new token for the fully authenticated user
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token'
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            auth()->logout();
            return response()->json([
                'message' => 'Logged out Successfully',
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();
            return response()->json([
                'user' => $user ,
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth()->refresh();
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Token could not be refreshed'
            ], 401);
        }
    }

    // public function resendOtp(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'register_token' => 'required|string'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'error' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::where('register_token', $request->register_token)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'error' => 'Invalid registration token'
    //         ], 404);
    //     }

    //     // Resend OTP
    //     $otp = OtpService::sendOtp($user->phone);

    //     return response()->json([
    //         'message' => 'OTP resent successfully'
    //     ]);
    // }

    // protected function getNextStepEndpoint(User $user): string
    // {
    //     // Determine which step the user needs to complete
    //     if (!$user->children()->exists()) {
    //         return 'api/register/step2';
    //     }

    //     $child = $user->children()->latest()->first();

    //     if (!$child->medicalInfo()->exists()) {
    //         return 'api/register/step3';
    //     }

    //     if (!$child->ability()->exists()) {
    //         return 'api/register/step4';
    //     }

    //     return 'api/register/step2'; // Default to step2
    // }
}
