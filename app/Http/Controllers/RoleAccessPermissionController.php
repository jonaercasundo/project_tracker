<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;

class RoleAccessPermissionController extends Controller
{
    /**
     * Admin Dashboard
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::with(['roles', 'companies'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(5)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        $companies = Company::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.dashboard', compact(
            'users',
            'roles',
            'companies'
        ));
    }

    /**
     * Role Access Management Page
     */
    public function edit(Request $request)
    {
        $search = $request->input('search');

        $users = User::with(['roles', 'companies'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        $companies = Company::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.roleaccess.edit', compact(
            'users',
            'roles',
            'companies'
        ));
    }

    /**
     * Update User Roles
     *
     * NOTE:
     * This currently uses Spatie's global roles.
     * Company-specific roles will be implemented when
     * Spatie Teams/company context is enabled.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,user_id',
            ],

            // MULTIPLE COMPANIES
            'company_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'company_ids.*' => [
                'integer',
                'exists:companies,company_id',
            ],

            // ROLES
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

        /*
        * Update company access
        *
        * This allows the user to belong to multiple companies.
        */
        $user->companies()->sync(
            $validated['company_ids']
        );

        /*
        * Current implementation:
        * Global Spatie roles.
        *
        * Later we can change this to company-specific
        * roles using Spatie Teams.
        */
        $user->syncRoles(
            $validated['roles']
        );

        /*
        * Keep legacy users.role column synchronized.
        */
        $user->update([
            'role' => $validated['roles'][0],
        ]);

        return back()->with(
            'success',
            'User company access and role updated successfully.'
        );
    }

    /**
     * Delete User
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'user_id' => [
                'required',
                'exists:users,user_id',
            ],
        ]);

        $user = User::findOrFail($request->user_id);

        $user->delete();

        return back()->with(
            'success',
            'User removed successfully.'
        );
    }
}