<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $roles = Role::query()->where('guard_name', 'web')->withCount(['users', 'permissions'])
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create');
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $name = trim($validated['name']);
        $this->ensureUniqueName($name);

        $role = Role::query()->create(['name' => $name, 'guard_name' => 'web']);
        $role->description = trim((string) ($validated['description'] ?? '')) ?: null;
        $role->save();
        $audit->record($request->user(), 'role.created', 'role', $role->getKey(), 'Created role '.$role->name.'.', $request->user()->currentCompany()?->getKey(), ['guard' => 'web']);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        abort_unless($role->guard_name === 'web', 404);

        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role, AdminAuditLogger $audit): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $name = trim($validated['name']);
        $this->ensureUniqueName($name, $role->getKey());

        if ($role->name === 'Administrator' && $name !== 'Administrator') {
            throw ValidationException::withMessages(['name' => 'The system Administrator role name is protected.']);
        }
        if ($name !== $role->name && $role->users()->exists()) {
            throw ValidationException::withMessages(['name' => 'A role assigned to users cannot be renamed. Reassign those users first.']);
        }

        $before = ['name' => $role->name, 'description' => $role->description];
        $role->name = $name;
        $role->description = trim((string) ($validated['description'] ?? '')) ?: null;
        $role->save();
        $audit->record($request->user(), 'role.updated', 'role', $role->getKey(), 'Updated role '.$role->name.'.', $request->user()->currentCompany()?->getKey(), [
            'before' => $before,
            'after' => ['name' => $role->name, 'description' => $role->description],
        ]);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role, AdminAuditLogger $audit): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        if ($role->name === 'Administrator') {
            throw ValidationException::withMessages(['role' => 'The system Administrator role cannot be deleted.']);
        }
        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'This role is assigned to users. Reassign them before deleting the role.']);
        }

        $roleName = $role->name;
        DB::transaction(fn () => $role->delete());
        $audit->record($request->user(), 'role.deleted', 'role', $role->getKey(), 'Deleted role '.$roleName.'.', $request->user()->currentCompany()?->getKey());

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    private function ensureUniqueName(string $name, ?int $exceptId = null): void
    {
        $duplicate = Role::query()->where('guard_name', 'web')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'A role with this name already exists, ignoring letter case.']);
        }
    }
}
