<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Check user existence
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        // 🔒 Block login if email is NOT verified
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.'
            ], 403);
        }

        // Generate token
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful!',
            'user'    => $user,
            'token'   => $token,
        ], 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
