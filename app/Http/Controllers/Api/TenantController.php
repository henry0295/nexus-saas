<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TenantController
{
    /**
     * Get current tenant information
     */
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;

        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'status' => $tenant->status,
            'plan' => $tenant->plan,
            'created_at' => $tenant->created_at,
            'credits' => $tenant->credits,
            'user_count' => $tenant->users()->count(),
        ]);
    }

    /**
     * Update tenant settings (name, email, plan, status)
     * Only tenant admin can do this
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        // Only admins can update tenant settings
        if ($user->role !== 'admin' && $user->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
        ]);

        $tenant = $user->tenant;
        $tenant->update($validated);

        return response()->json([
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Get all users in the current tenant
     */
    public function users(Request $request)
    {
        $users = User::where('tenant_id', $request->user()->tenant_id)
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * Add new user to tenant
     * Only tenant admin can do this
     */
    public function addUser(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin' && $user->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:user,admin',
            'password' => 'required|min:8|confirmed',
        ]);

        $newUser = User::create([
            'tenant_id' => $user->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'User added successfully',
            'user' => $newUser->only(['id', 'name', 'email', 'role', 'status']),
        ], 201);
    }

    /**
     * Update user role or status
     * Only tenant admin can do this
     */
    public function updateUser(Request $request, User $user)
    {
        $requestUser = $request->user();
        
        if ($requestUser->role !== 'admin' && $requestUser->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify user belongs to same tenant
        if ($user->tenant_id !== $requestUser->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can't modify superadmin or self
        if ($user->role === 'superadmin' || $user->id === $requestUser->id) {
            return response()->json(['error' => 'Cannot modify this user'], 400);
        }

        $validated = $request->validate([
            'role' => 'nullable|in:user,admin',
            'status' => 'nullable|in:active,suspended',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->only(['id', 'name', 'email', 'role', 'status']),
        ]);
    }

    /**
     * Remove user from tenant
     * Only tenant admin can do this
     */
    public function removeUser(Request $request, User $user)
    {
        $requestUser = $request->user();
        
        if ($requestUser->role !== 'admin' && $requestUser->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify user belongs to same tenant
        if ($user->tenant_id !== $requestUser->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can't delete superadmin or self
        if ($user->role === 'superadmin' || $user->id === $requestUser->id) {
            return response()->json(['error' => 'Cannot delete this user'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
