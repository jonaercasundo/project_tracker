<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'employee_id' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],

            // MULTIPLE COMPANIES
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['integer', 'exists:companies,company_id'],

            // ROLE
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],

            // PASSWORD
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['email'],
                'employee_id' => $validated['employee_id'],
                'department' => $validated['department'],
                'position' => 'Staff',

                // Legacy users.role column
                'role' => $validated['roles'][0],

                'password' => Hash::make($validated['password']),
            ]);

            // Attach multiple companies
            $user->companies()->attach($validated['company_ids']);

            // Assign role
            $user->assignRole($validated['roles']);

            return back()->with(
                'success',
                'User created successfully.'
            );

        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->withErrors([
                    'error' => $e->getMessage()
                ]);
        }
    }


    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,user_id',
            ],

            'company_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'company_ids.*' => [
                'integer',
                'exists:companies,company_id',
            ],

            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'string',
                'exists:roles,name',
            ],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Update company access
        $user->companies()->sync($validated['company_ids']);

        // Update Spatie roles
        $user->syncRoles($validated['roles']);

        // Keep legacy users.role column synchronized
        $user->update([
            'role' => $validated['roles'][0],
        ]);

        return back()->with(
            'success',
            'User company access and role updated successfully.'
        );
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with(
                'success',
                'User deleted successfully.'
            );
    }
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,user_id',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with(
            'success',
            'Password reset successfully for ' . $user->name . '.'
        );
    }
}