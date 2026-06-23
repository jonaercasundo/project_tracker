<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,name',
        ]);

        // Always guarantee username exists
        $username = $request->username 
            ?? strtolower(str_replace(' ', '.', trim($request->name))) . rand(100, 999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'employee_id' => $request->employee_id ?? null,
            'department' => $request->department ?? null,
            'position' => $request->position ?? null,
            'password' => Hash::make($request->password),
        ]);

        // Spatie role assignment (single source of truth)
        $user->assignRole($request->role);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);

        // Sync roles properly (Spatie handles everything)
        $user->syncRoles([$request->role]);

        return back()->with('success', 'Role updated successfully.');
    }
}