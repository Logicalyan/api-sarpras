<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Custom\Format;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('profile')->get();
        return Format::apiResponse(200, 'List of users', $users);
    }

    public function show($id)
    {
        $user = User::with('profile')->find($id);
        if (!$user) {
            return Format::apiResponse(404, 'User not found');
        }
        return Format::apiResponse(200, 'User detail', $user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,borrower',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role']
            ]);

            $user->profile()->create([
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null
            ]);

            DB::commit();

            return Format::apiResponse(201, 'User created successfully', $user->load('profile'));
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to create user', null, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return Format::apiResponse(404, 'User not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'role' => 'sometimes|in:admin,borrower',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'password' => isset($validated['password']) ? Hash::make($validated['password']) : $user->password,
                'role' => $validated['role'] ?? $user->role
            ]);

            $user->profile()->updateOrCreate([], [
                'phone' => $validated['phone'] ?? $user->profile->phone ?? null,
                'address' => $validated['address'] ?? $user->profile->address ?? null
            ]);

            DB::commit();

            return Format::apiResponse(200, 'User updated successfully', $user->load('profile'));
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to update user', null, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return Format::apiResponse(404, 'User not found');
        }

        DB::beginTransaction();

        try {
            $user->delete();
            DB::commit();
            return Format::apiResponse(200, 'User deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to delete user', null, $e->getMessage());
        }
    }
}
