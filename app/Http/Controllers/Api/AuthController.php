<?php

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $tenant = Tenant::create([
            'name' => $validated['company_name'],
            'email' => $validated['email'],
            'status' => 'trial',
            'plan' => 'starter',
        ]);

        // Crear usuario admin del tenant
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['company_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Crear créditos iniciales (100 trial)
        $tenant->credits()->create([
            'balance' => 100,
            'total_purchased' => 0,
            'total_used' => 0,
        ]);

        // Crear integración
        $tenant->integrations()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
            'tenant' => $tenant,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        if (!$user->isActive()) {
            return response()->json(['error' => 'Account suspended'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'tenant' => $user->tenant,
            'credits' => $user->tenant?->credits,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'tenant' => $request->user()->tenant,
            'credits' => $request->user()->tenant?->credits,
        ]);
    }
}
