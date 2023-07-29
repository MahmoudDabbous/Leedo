<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|confirmed'
        ]);


        $code = Str::random(6);
        $user = new User();

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->verification_code = $code;
        $user->save();


        Log::info('Verification code is ' . $code);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken("access_token")->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $request->all([
            'phone' => 'required',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only(['phone', 'password']))) {
            return response()->json([
                'message' => 'Phone or Password are wrong.',
            ], 401);
        }

        $user = User::where('phone', $request->phone)->first();
        $user->tokens()->delete();

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'User not verified.',
            ], 401);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('access_token')->plainTextToken,
        ], 200);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('verification_code', $request->code)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid verification code'], 401);
        }

        $user->markEmailAsVerified();
        $user->verification_code = null;
        $user->save();

        return response()->json(['message' => 'Verification successful']);
    }
}
