<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;

Route::prefix('auth')->group(function () {

    Route::post('/send-otp', [
        AuthController::class,
        'sendOtp'
    ]);

    Route::post('/verify-otp', [
        AuthController::class,
        'verifyOtp'
    ]);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [
        AuthController::class,
        'me'
    ]);

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::post('/profile', [
        ProfileController::class,
        'update'
    ]);

    Route::post('/profile/update', [
        ProfileController::class,
        'update'
    ]);

    Route::post('/profile/setup', [
        ProfileController::class,
        'setup'
    ]);

    Route::get('/users', [
        UserController::class,
        'index'
    ]);

    Route::get('/users/{id}', [
        UserController::class,
        'show'
    ]);
});
