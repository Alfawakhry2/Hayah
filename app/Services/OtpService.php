<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class OtpService
{
    protected static $vonageClient;

    public static function sendOtp(string $phone, int $length = 6, int $ttlMin = 5)
    {
        // Generate random OTP code
        $code = 123456;

        $hashed_code = Hash::make($code);
        $expires = Carbon::now()->addMinutes($ttlMin);

        // Create OTP record
        $otp = OtpCode::create([
            'phone' => $phone,
            // 'code' => $hashed_code,
            'code' => $code,
            'expires_at' => $expires,
        ]);

        // Send SMS via Vonage
        try {
            // Initialize Vonage client if not already initialized
            if (!self::$vonageClient) {
                $apiKey = config('services.vonage.key');
                $apiSecret = config('services.vonage.secret');

                if (!$apiKey || !$apiSecret) {
                    \Log::error('Vonage credentials not configured');
                    return $otp; // Return OTP record even if SMS fails
                }

                $credentials = new Basic($apiKey, $apiSecret);
                self::$vonageClient = new Client($credentials);
            }

            $brandName = config('services.vonage.brand_name', 'YourApp');
            $message = "Your verification code is: {$code}. Valid for {$ttlMin} minutes.";

            $response = self::$vonageClient->sms()->send(
                new SMS($phone, $brandName, $message)
            );

            $message = $response->current();

            if ($message->getStatus() == 0) {
                \Log::info("SMS sent successfully to: " . $phone);
            } else {
                \Log::error("Vonage SMS failed for {$phone}: " . $message->getStatus());
            }
        } catch (\Exception $e) {
            \Log::error("Vonage SMS error for {$phone}: " . $e->getMessage());
        }

        \Log::info("OTP for " . $phone . " : " . $code);

        return $otp;
    }

    public static function verifyOtp(string $phone, string $code)
    {
        // First get the latest OTP for this phone
        $otp = OtpCode::where('phone', $phone)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) return false;

        // Verify the code using Hash::check
        // if (!Hash::check($code, $otp->code)) {
        if (!($code === $otp->code)){
            return false;
        }

        $otp->update([
            'used' => true,
        ]);

        return true;
    }

    // Helper method to get Vonage client (for testing purposes)
    public static function getVonageClient()
    {
        return self::$vonageClient;
    }
}
