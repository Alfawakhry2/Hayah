<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginController extends Controller
{
    use ApiResponse;
    public function login(Request $request): JsonResponse|ApiResource
    {
        $validator = Validator::make($request->all(), [
            'phone_code' => 'required|exists:countries,phone_code',
            'phone' => 'required|string',
            // to not change the endPoint
            'otp'   => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ApiResource::make(status_code: 422, message: $validator->errors()->first());
        }

        // 1. Check if user exists
        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();
        if (!$user) {
            return ApiResource::make(status_code: 404, message: 'User Not Found');
        }

        if (!$request->input('otp')) {
            OtpService::sendOtp($user->phone_code . $user->phone);
            return ApiResource::make(
                status_code: 200,
                message: 'OTP sent , Please Verify To Continue',
                data: [
                    'next_endpoint' => 'api/auth/login',
                ]
            );
        }

        // 3. لو OTP موجود → نتحقق منه
        if (!OtpService::verifyOtp($user->phone_code . $user->phone, $request->otp)) {
            return ApiResource::make(
                status_code: 422,
                message: 'Invalid Or Expired OTP'
            );
        }

        // 4. لو OTP صح → نشوف حالة التسجيل
        $nextStep = $this->getNextStepEndpoint($user);
        // dd($nextStep);
        if ($nextStep !== null) {
            return ApiResource::make(
                status_code: 403,
                message: 'Registration not complete',
                data: [
                    'registration_token' => $user->registration_token,
                    'next_endpoint' => $nextStep,
                ]
            );
        }

        $token = JWTAuth::fromUser($user);



        return ApiResource::make(
            status_code: 200,
            message: 'Successful Login',
            data: [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        );
    }

    public function logout(): JsonResponse|ApiResource
    {
        try {
            auth()->logout();
            return ApiResource::make(status_code: 200, message: 'Logged out Successfully');
        } catch (JWTException $e) {
            return ApiResource::make(status_code: 500, message: 'Failed To Logout');
        }
    }

    public function me(): JsonResponse|ApiResource
    {
        try {
            $user = auth()->user();
            $user = User::where('id', $user->id)
                ->with([
                    'country',
                    'nationality',
                    'children'
                ])
                ->first();

            return ApiResource::make(status_code: 200, message: 'User Information', data: $user);
        } catch (JWTException $e) {
            return  ApiResource::make(status_code: 404, message: 'User Not Found');
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

    protected function get0NextStepEndpoint(User $user)
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
    protected function getNextStepEndpoint(User $user)
    {
        // Determine which step the user needs to complete
        if (!$user->children()->exists()) {
            return [
                'next_endpoint' => 'api/register/step2',
            ];
        }

        $child = $user->children()->latest()->first();

        if (!$child->medicalInfo()->exists()) {
            return  [
                'next_endpoint' => 'api/register/step3',
            ];
        }

        if (!$child->ability()->exists()) {
            return [
                'next_endpoint' => 'api/register/step4',
            ];
        }

        return null;
    }
}
