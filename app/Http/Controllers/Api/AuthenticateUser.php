<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthenticateUser extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate(['email' => 'required', 'password' => 'required', 'token_name' => 'required|string']);
        if (Auth::attempt($request->only(['email', 'password']))) {

            $token = $request->user()->createToken($request->token_name);

            return response()->json(['token' => $token->plainTextToken]);
        }
        return response()->noContent();
    }
}
