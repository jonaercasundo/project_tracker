<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleAccessPermissionController extends Controller
{
       public function edit()
    {
        // Get all users WITH their roles (Spatie)
        $users = User::with('roles')->get();

        // Get all available roles
        $roles = Role::all();

        return view('roleaccess.edit', compact('users', 'roles'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);

        // Spatie way
        $user->syncRoles([$request->role]);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        $user->delete(); // or revoke role instead

        return back()->with('success', 'User removed successfully.');
    }
}