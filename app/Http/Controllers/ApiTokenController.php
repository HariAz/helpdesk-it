<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()->orderByDesc('created_at')->get();
        return view('settings.api-tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'token_name' => 'required|string|max:80',
            'abilities'  => 'array',
            'abilities.*' => 'string',
        ]);

        $abilities = $data['abilities'] ?? ['*'];
        $token = $request->user()->createToken($data['token_name'], $abilities);

        return back()->with('new_token', $token->plainTextToken)->with('token_name', $data['token_name']);
    }

    public function destroy(Request $request, int $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();
        return back()->with('success', 'Token dicabut.');
    }
}
