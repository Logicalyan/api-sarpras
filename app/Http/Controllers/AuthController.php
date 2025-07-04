<?php

namespace App\Http\Controllers;

use App\Custom\Format; // pastikan ini ditambahkan di atas
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(Request $request)
    {

        $credentials = $request->validate([
            'identifier' => 'required|string',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['identifier'])
            ->orWhere('name', $credentials['identifier'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return Format::apiResponse(401, 'Invalid credentials', null, ['error' => 'Invalid email/username or password']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return Format::apiResponse(200, 'Login successful', [
            'token' => $token,
            'user' => $user->load('profile')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return Format::apiResponse(200, 'Logout successful');
    }

    public function me(Request $request)
    {
        return Format::apiResponse(200, 'User profile', $request->user());
    }
}
