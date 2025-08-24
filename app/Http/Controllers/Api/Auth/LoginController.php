<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_code' => 'required|exists:countries,phone_code',
            'phone' => 'required|string',
            // to not change the endPoint
            'otp'   => 'nullable|string'
        ]);

        if ($validator->fails()) {
            // return response()->json([
            //     'error' => 'Validation failed',
            //     'errors' => $validator->errors()
            // ], 422);
            return $this->errorResponse(422, 'Validation Faild', $validator->errors());
        }

        // 1. Check if user exists
        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();
        if (!$user) {
            // return response()->json([
            //     'error' => 'User not found'
            // ], 404);
            return $this->errorResponse(404, 'User Not Found');
        }

        if (!$request->input('otp')) {
            OtpService::sendOtp($user->phone_code . $user->phone);

            // return response()->json([
            //     'message' => 'OTP sent. Please verify to continue.',
            //     'next_endpoint' => 'api/auth/login (with otp)'
            // ], 200);

            return $this->successResponse(200, 'OTP sent , Please Verify To Continue', [
                'next_endpoint' => 'api/auth/login',
            ]);
        }

        // 3. لو OTP موجود → نتحقق منه
        if (!OtpService::verifyOtp($user->phone_code . $user->phone, $request->otp)) {
            // return response()->json([
            //     'error' => 'Invalid or expired OTP'
            // ], 401);
            return $this->errorResponse(401, 'Invalid Or Expired OTP');
        }

        // 4. لو OTP صح → نشوف حالة التسجيل
        $nextStep = $this->getNextStepEndpoint($user);
        if ($nextStep !== null) {
            return response()->json([
                'message' => 'Registration not complete',
                'registration_token' => $user->registration_token,
                'next_endpoint' => $nextStep,
            ], 403);
        }

        $token = JWTAuth::fromUser($user);

        // return response()->json([
        //     'message' => 'Login successful',
        //     'access_token' => $token,
        //     'token_type' => 'bearer',
        //     'expires_in' => auth('api')->factory()->getTTL() * 60
        // ], 200);

        return $this->successResponse(200, 'Successful Login', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    public function logout(): JsonResponse
    {
        try {
            auth()->logout();
            // return response()->json([
            //     'message' => 'Logged out Successfully',
            // ], 200);
            return $this->successResponse(200, 'Logged out Successfully');
        } catch (JWTException $e) {
            // return response()->json([
            //     'error' => 'Failed to logout, please try again'
            // ], 500);
            return $this->errorResponse(500, 'Failed To Logout');
        }
    }

    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();
            $user = User::where('id' , $user->id)->with(['country' , 'nationality' , 'children'])->get();
            // return response()->json([
            //     'user' => $user,
            // ], 200);
            return $this->successResponse(200, 'User Information', [
                'notification_counter'=>0,
                'user' => $user,
            ]);
        } catch (JWTException $e) {
            // return response()->json([
            //     'error' => 'User not found'
            // ], 404);
            return $this->errorResponse(404, 'User Not Found');
        }
    }

    // public function refresh(): JsonResponse
    // {
    //     try {
    //         $token = auth()->refresh();
    //         return response()->json([
    //             'access_token' => $token,
    //             'token_type' => 'bearer',
    //             'expires_in' => auth('api')->factory()->getTTL() * 60
    //         ]);
    //     } catch (JWTException $e) {
    //         return response()->json([
    //             'error' => 'Token could not be refreshed'
    //         ], 401);
    //     }
    // }

    protected function getNextStepEndpoint(User $user)
    {
        // Determine which step the user needs to complete
        if (!$user->children()->exists()) {
            // return 'api/register/step2';
            return $this->errorResponse(403, 'Should Register Your Child First !', [
                'next_endpoint' => 'api/register/step2',
            ]);
        }

        $child = $user->children()->latest()->first();

        if (!$child->medicalInfo()->exists()) {
            // return 'api/register/step3';
            return $this->errorResponse(403, 'Should Enter Medical Information !', [
                'next_endpoint' => 'api/register/step3',
            ]);
        }

        if (!$child->ability()->exists()) {
            // return 'api/register/step4';
            return $this->errorResponse(403, 'Should Enter Children Ability Information !', [
                'next_endpoint' => 'api/register/step4',
            ]);
        }

        return null;
    }
}
