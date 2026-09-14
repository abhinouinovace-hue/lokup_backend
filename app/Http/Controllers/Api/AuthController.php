<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $normalizedPhone = preg_replace('/\D+/', '', $request->phone);

        $isExistingUser = User::where('phone', $normalizedPhone)->exists();

        $otp = random_int(1000, 9999);

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
            'message' => 'OTP sent successfully',
            'phone' => $normalizedPhone,
            'otp' => $otp,
            'is_existing_user' => $isExistingUser,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'otp' => 'required|digits:4',
        ]);

        $normalizedPhone = preg_replace('/\D+/', '', $request->phone);

        $otpRecord = DB::table('otp_verifications')
            ->where('phone', $normalizedPhone)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'OTP not found',
            ], 404);
        }

        if (now()->greaterThan($otpRecord->expires_at)) {
            DB::table('otp_verifications')
                ->where('phone', $normalizedPhone)
                ->delete();

            return response()->json([
                'success' => false,
                'message' => 'OTP has expired',
            ], 422);
        }

        if (!Hash::check($request->otp, $otpRecord->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 422);
        }

        DB::table('otp_verifications')
            ->where('phone', $normalizedPhone)
            ->delete();

        $user = User::firstOrCreate(
            ['phone' => $normalizedPhone],
            [
                'name' => 'New User',
                'email' => 'user' . $normalizedPhone . '@lokup.local',
                'password' => Hash::make(Str::random(32)),
                'coins' => 150,
                'status' => true,
            ]
        );

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
