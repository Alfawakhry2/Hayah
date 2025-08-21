<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Public authentication routes

Route::post('/register/step1', [RegisterController::class, 'step1']);
Route::post('/register/verify-phone', [RegisterController::class, 'verifyPhone']);
// Route::post('/resend-otp', [LoginController::class, 'resendOtp']);

Route::post('/register/step2', [RegisterController::class, 'step2']);
Route::post('/register/step3', [RegisterController::class, 'step3']);
Route::post('/register/step4', [RegisterController::class, 'step4']);


Route::post('/login', [LoginController::class, 'login']);
    // Protected routes (require JWT token)
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/refresh', [LoginController::class, 'refresh']);
        Route::get('/me', [LoginController::class, 'me']);
    });
