<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleAccessPermissionController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('roleaccess.index', $request->query());
    }

    public function edit(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
        ]);
        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? 'all';
        $users = User::query()->with(['roles', 'companies'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('department', 'like', '%'.$search.'%')->orWhere('employee_id', 'like', '%'.$search.'%');
            }))
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
        $companies = Company::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.roleaccess.edit', compact('users', 'roles', 'companies', 'status'));
    }

    public function update(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,user_id'],
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['required', 'integer', 'exists:companies,company_id'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'is_active' => ['required', 'boolean'],
        ]);
        $target = User::query()->findOrFail($validated['user_id']);
        $newRoleNames = array_values(array_unique($validated['roles']));
        $isActive = (bool) $validated['is_active'];
        if ($target->getKey() === $request->user()->getKey() && (! $isActive || ! in_array('Administrator', $newRoleNames, true))) {
            throw ValidationException::withMessages(['roles' => 'You cannot deactivate your own account or remove your own Administrator access.']);
        }
        if ($target->can('ai.settings.manage')
            && ! in_array('Administrator', $newRoleNames, true)) {
            throw ValidationException::withMessages(['roles' => 'An account with AI configuration access must keep the Administrator role.']);
        }

        DB::transaction(function () use ($request, $target, $validated, $newRoleNames, $isActive, $audit): void {
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $removesAdminAccess = $target->hasRole('Administrator') && (! in_array('Administrator', $newRoleNames, true) || ! $isActive);
            if ($removesAdminAccess) {
                $activeAdministrators = User::query()->role('Administrator', 'web')->where('is_active', true)->lockForUpdate()->get();
                if ($activeAdministrators->count() <= 1) {
                    throw ValidationException::withMessages(['roles' => 'The last active Administrator must remain active with Administrator access.']);
                }
            }

            $before = [
                'roles' => $target->getRoleNames()->values()->all(),
                'company_ids' => $target->companies()->pluck('companies.company_id')->map(fn ($id): int => (int) $id)->all(),
                'is_active' => (bool) $target->is_active,
            ];
            $target->companies()->sync(array_map('intval', $validated['company_ids']));
            $target->syncRoles($newRoleNames);
            $target->role = $newRoleNames[0];
            $target->is_active = $isActive;
            $target->save();
            $audit->record($request->user(), 'user.access_updated', 'user', $target->getKey(), 'Updated access for '.$target->name.'.', $request->user()->currentCompany()?->getKey(), [
                'before' => $before,
                'after' => ['roles' => $newRoleNames, 'company_ids' => array_map('intval', $validated['company_ids']), 'is_active' => $isActive],
            ]);
        });

        return back()->with('success', 'User role, company access, and account status updated.');
    }

    public function destroy(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,user_id']]);
        $target = User::query()->findOrFail($validated['user_id']);
        if ($target->getKey() === $request->user()->getKey()) {
            throw ValidationException::withMessages(['user_id' => 'You cannot remove your own account.']);
        }

        DB::transaction(function () use ($target, $request, $audit): void {
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());
            if ($target->hasRole('Administrator') && User::query()->role('Administrator', 'web')->where('is_active', true)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages(['user_id' => 'The last active Administrator cannot be removed.']);
            }
            $name = $target->name;
            $id = $target->getKey();
            $target->delete();
            $audit->record($request->user(), 'user.deleted', 'user', $id, 'Removed user '.$name.'.', $request->user()->currentCompany()?->getKey());
        });

        return back()->with('success', 'User removed successfully.');
    }
}
