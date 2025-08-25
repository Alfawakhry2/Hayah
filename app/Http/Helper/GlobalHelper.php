<?php

namespace App\Http\Helper;

use App\Http\Resources\ApiResource;

class GlobalHelper
{
    public static function uploadImage($image, $path)
    {
        $name = time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path($path), $name);
        return $name;
    }
    public static function checkPhoneVerification($phone_verified_at)
    {
        if ($phone_verified_at == null) {
            abort(ApiResource::make(
                status_code: 422,
                message: 'Phone Verification Required',
                data: [
                    'next_endpoint' => 'api/register/verify-phone'
                ]
            ));
        }
    }
}
