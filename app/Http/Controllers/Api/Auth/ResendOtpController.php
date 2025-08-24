<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class ResendOtpController extends Controller
{

    use ApiResponse;

    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_code' => 'required',
            'phone' => 'required|string'
        ]);

        if ($validator->fails()) {
            // return response()->json([
            //     'error' => 'Validation failed',
            //     'errors' => $validator->errors()
            // ], 422);

            return $this->errorResponse(422 , 'Validation Failed' , $validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            // return response()->json([
            //     'error' => 'Invalid Mobil Phone Not Exist'
            // ], 404);

            return $this->errorResponse(404 , 'Invalid phone numer' , 'Invalid phone numer');
        }

        // Resend OTP
        $otp = OtpService::sendOtp($user->phone);

        // return response()->json([
        //     'message' => 'OTP resent successfully'
        // ]);

        return $this->successResponse(200 , 'OTP resent Successfully');
    }
}
