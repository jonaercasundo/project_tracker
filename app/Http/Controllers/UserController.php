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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username'    => $request->username ?? strtolower(explode(' ', $request->name)[0]) . rand(100, 999),
            'employee_id' => $request->employee_id,
            'department' => $request->department,
            'position' => $request->position,
            'password' => bcrypt($request->password),
        ]);

        // ✅ Spatie (REAL role system)
        $user->assignRole($request->role);

        // 🟡 OPTIONAL: store label only (no logic depends on this)
        $user->role = $request->role;
        $user->save();

        return back()->with('success', 'User created successfully.');
    }
    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);

        // Spatie update
        $user->syncRoles([$request->role]);

        // optional mirror column
        $user->role = $request->role;
        $user->save();

        return back()->with('success', 'Role updated successfully.');
    }
}
