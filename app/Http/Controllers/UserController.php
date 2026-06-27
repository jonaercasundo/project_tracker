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

        try {

            $user = User::create([
                'name'        => $request->name,
                'email'       => $request->email,
                'username'    => $request->email,
                'employee_id' => $request->employee_id,
                'department'  => $request->department,
                'position'    => $request->position,
                'role'        => $request->role,
                'password'    => Hash::make($request->password),
            ]);
            $user->assignRole($request->role);

            return back()->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getTraceAsString());
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);

        // Sync roles properly (Spatie handles everything)
        $user->syncRoles([$request->role]);

        return back()->with('success', 'Role updated successfully.');
    }
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'User deleted successfully.');
    }
}