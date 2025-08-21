<?php
namespace App\Services ;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class OtpService {


    public static function sendOtp(string $phone ,int $length = 6,int $ttlMin=5){

        // $code = str_pad((string)random_int(0 , (int)pow(10 , $length)-1) , $length , '0' , STR_PAD_LEFT);
        $code = 12456 ;

        $hashed_code = Hash::make($code);

        $expires = Carbon::now()->addMinutes($ttlMin);

        $otp = OtpCode::create([
            'phone'=> $phone ,
            'code' =>$hashed_code,
            'expires_at'=>$expires ,
        ]);

        \Log::info("OTP for " . $phone . " : " . $code );

        return $otp ;
    }


    static public function verifyOtp(string $phone , string $code){
        $otp = OtpCode::where('phone' , $phone)
                        ->where('code' , $code)
                        ->where('used' , false)
                        ->where('expires_at' , '>' , Carbon::now())
                        ->latest()
                        ->first();


            if(!$otp) return false ;
            $otp->update([
                'used'=>true ,
            ]);
            return true ;
    }
}
