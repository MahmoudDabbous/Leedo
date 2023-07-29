<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|min:6|confirmed'
        ]);

        if ($data->fails()) {
            return response()->json($data->errors(), Response::HTTP_BAD_REQUEST);
        }


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
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only(['phone', 'password']))) {
            return response()->json([
                'message' => 'Phone or Password are wrong.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::where('phone', $request->phone)->first();
        $user->tokens()->delete();

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'User not verified.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('access_token')->plainTextToken,
        ], Response::HTTP_OK);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('verification_code', $request->code)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid verification code'], Response::HTTP_UNAUTHORIZED);
        }

        $user->markEmailAsVerified();
        $user->verification_code = null;
        $user->save();

        return response()->json(['message' => 'Verification successful'], Response::HTTP_OK);
    }
}
