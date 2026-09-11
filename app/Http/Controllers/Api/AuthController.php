<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Send OTP
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = trim($request->phone);
        $normalizedPhone = preg_replace('/\D+/', '', $phone);

        if ($normalizedPhone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number',
            ], 422);
        }

        $isExistingUser = User::where('phone', $normalizedPhone)->exists();

        // Generate a new OTP for each request and keep only its hash in MySQL.
        $previousOtp = DB::table('otp_verifications')
            ->where('phone', $normalizedPhone)
            ->value('otp');

        do {
            $otp = (string) random_int(100000, 999999);
        } while ($previousOtp && Hash::check($otp, $previousOtp));

        DB::table('otp_verifications')->updateOrInsert(
            ['phone' => $normalizedPhone],
            [
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $isExistingUser
                ? 'OTP sent successfully. This phone number is already registered.'
                : 'OTP sent successfully',
            'phone' => $normalizedPhone,
            'is_existing_user' => $isExistingUser,
            'otp' => $otp,
        ]);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = trim($request->phone);
        $normalizedPhone = preg_replace('/\D+/', '', $phone);

        if ($normalizedPhone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number',
            ], 422);
        }

        $otpVerification = DB::table('otp_verifications')
            ->where('phone', $normalizedPhone)
            ->first();

        if (! $otpVerification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 401);
        }

        if (Carbon::parse($otpVerification->expires_at)->isPast()) {
            DB::table('otp_verifications')->where('phone', $normalizedPhone)->delete();
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 401);
        }

        if (! Hash::check($request->otp, $otpVerification->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 401);
        }

        // An OTP can be used only once.
        DB::table('otp_verifications')->where('phone', $normalizedPhone)->delete();

        /*
        |--------------------------------------------------------------------------
        | Create or find user
        |--------------------------------------------------------------------------
        |
        | Your users table requires the name field.
        | We use "New User" initially.
        | The Flutter name-selection screen can update it later.
        |
        */

        try {
            $user = User::firstOrCreate(
                [
                    'phone' => $normalizedPhone,
                ],
                [
                    'name' => 'New User',
                    'email' => 'user' . $normalizedPhone . '@lokup.local',
                    'password' => Hash::make(Str::random(32)),
                    'coins' => 150,
                    'status' => true,
                ]
            );

            $isNewUser = $user->wasRecentlyCreated;

            // Create Sanctum token
            $token = $user->createToken('flutter-app')->plainTextToken;
        } catch (QueryException $exception) {
            Log::error('OTP verification failed while accessing the database.', [
                'phone' => $normalizedPhone,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database connection failed. Please start MySQL and try again.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => $isNewUser
                ? 'OTP verified successfully. New account created.'
                : 'OTP verified successfully. Welcome back.',
            'is_existing_user' => ! $isNewUser,
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get logged-in user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
