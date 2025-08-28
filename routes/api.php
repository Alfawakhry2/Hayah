<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PersonalController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResendOtpController;
use App\Http\Controllers\Api\User\ProfileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Public authentication routes

Route::post('/register/step1', [RegisterController::class, 'step1']);
Route::post('/register/verify-phone', [RegisterController::class, 'verifyPhone']);
Route::post('/register/step2', [RegisterController::class, 'step2']);
Route::post('/register/step3', [RegisterController::class, 'step3']);
Route::post('/register/step4', [RegisterController::class, 'step4']);


Route::post('/resend-otp', [ResendOtpController::class, 'resendOtp']);


Route::post('/login', [LoginController::class, 'login']);
    // Protected routes (require JWT token)
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/refresh', [LoginController::class, 'refresh']);
        Route::get('/me', [LoginController::class, 'me']);
    });



    // dropdowns
Route::get('countries', [PersonalController::class, 'countries']);                // list countries
Route::get('countries/{id}/governorates', [PersonalController::class, 'governorates']); // governorates per country
Route::get('nationalities', [PersonalController::class, 'nationalities']);      // list nationalities



//user

Route::middleware('auth:api')->group(function(){
    Route::get('user/profile' , [ProfileController::class , 'show']);
    Route::match(['put' , 'patch'] , 'user/profile' , [ProfileController::class , 'update']);
    Route::delete('user/profile' , [ProfileController::class , 'destroy']);
});
