<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Lokup API is working'
    ]);
});

Route::prefix('auth')->group(function () {

    // Send OTP
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);

    // Verify OTP
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Get logged-in user
        Route::get('/me', [AuthController::class, 'me']);

        // Logout
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
