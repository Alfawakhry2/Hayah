<?php

namespace App\Http\Controllers\Api;

use App\Models\Country;
use App\Models\Governorate;
use App\Models\Nationality;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;

class PersonalController extends Controller
{
    use ApiResponse;
    public function countries(Request $request): ApiResource
    {
        $countries = Country::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('phone_code', 'like', '%' . $request->q . '%')
            ->orWhere('iso_code', 'like', '%' . $request->q . '%')
            ->get();
        return ApiResource::make(
            status_code: 200,
            message: 'All Countries Data',
            data: $countries
        );
    }

    public function governorates(Request $request, $countryId): ApiResource
    {
        $governorate = Governorate::where('country_id', $countryId)
            ->where(function ($query) use ($request) {
                $query->where('governorate_name', 'like', '%' . $request->q . '%');
            })
            ->get();
        return ApiResource::make(
            status_code: 200,
            message: 'Related Governorates',
            data: $governorate
        );
    }

    public function nationalities(Request $request): ApiResource
    {
        $nationalities = Nationality::where('name', 'like', '%' . $request->q . '%')->get();

        return ApiResource::make(
            status_code: 200,
            message: 'All Nationalities Data',
            data: $nationalities
        );
    }
}
