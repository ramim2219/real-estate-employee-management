<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
            'device_name' => ['required','string','max:100'],
        ]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)
            || ($user->employee && ! in_array($user->employee->status, ['active','on_leave'], true))) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials or inactive account.']);
        }
        return response()->json([
            'token' => $user->createToken($data['device_name'])->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user->only(['id','name','email']),
            'roles' => $user->getRoleNames(),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user->only(['id','name','email']),
            'roles' => $user->getRoleNames(),
            'employee' => $user->employee?->load('department','designation','workSchedule'),
        ]);
    }

    public function logout(Request $request)
    {
        // API token only. SPA session auth needs its own CSRF/session logout handler.
        $token = $request->user()->currentAccessToken();
        if ($token && method_exists($token, 'delete')) $token->delete();
        return response()->json(['message' => 'Logged out.']);
    }
}
