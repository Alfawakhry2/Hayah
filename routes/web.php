<?php

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    // dd($request->q);
    return Country::where('name', 'like', '%' . $request->q . '%')
        ->orWhere('phone_code', 'like', '%' . $request->q . '%')
        ->orWhere('iso_code', 'like', '%' . $request->q . '%')
        ->get();
});
