<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class TokenController extends Controller
{
    public function issue(Request $request)
    {
        $data = $request->validate([
            'email'      => 'required|email',
            'password'   => 'required',
            'token_name' => 'required|string|max:80',
            'abilities'  => 'array',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        $abilities = $data['abilities'] ?? ['*'];
        $token     = $user->createToken($data['token_name'], $abilities);

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'name'       => $data['token_name'],
            'abilities'  => $abilities,
            'user'       => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);
    }

    public function revoke(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Token dicabut.']);
    }

    public function list(Request $request)
    {
        $tokens = $request->user()->tokens()->select(['id', 'name', 'abilities', 'last_used_at', 'created_at'])->get();
        return response()->json(['data' => $tokens]);
    }
}
