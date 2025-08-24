<?php

namespace App\Http\Controllers\Api;

use App\Models\Country;
use App\Models\Governorate;
use App\Models\Nationality;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;

class PersonalController extends Controller
{
    use ApiResponse;
    public function countries()
    {
        $countries = Country::all();
        return $this->successResponse(200, 'All Countries Data', $countries);
    }

    public function governorates($countryId)
    {
        $governorate = Governorate::where('country_id' , $countryId)->get();
        return $this->successResponse(200, 'Related Governorates', $governorate);
    }

    public function nationalities()
    {
        $nationalities = Nationality::all();

        return $this->successResponse(200, 'All Nationalities Data', $nationalities);
    }
}
