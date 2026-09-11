<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('status', true)
            ->select([
                'id',
                'name',
                'phone',
                'gender',
                'language',
                'avatar',
                'coins',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }
}