<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AiCapabilityRegistry;
use App\Services\AiProviderConfiguration;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class AdminControlCenterController extends Controller
{
    public function dashboard(AiProviderConfiguration $configuration): View
    {
        $roles = Role::query()->where('guard_name', 'web');
        $activeUserCount = Schema::hasColumn('users', 'is_active') ? User::query()->where('is_active', true)->count() : null;
        $databaseHealthy = true;
        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $databaseHealthy = false;
        }

        $usage = Schema::hasTable('ai_activity_logs')
            ? DB::table('ai_activity_logs')->where('status', 'completed')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->selectRaw('COUNT(*) as request_count, COALESCE(SUM(COALESCE(input_tokens, 0)), 0) as input_tokens, COALESCE(SUM(COALESCE(output_tokens, 0)), 0) as output_tokens')->first()
            : (object) ['request_count' => null, 'input_tokens' => null, 'output_tokens' => null];

        $recentActivity = Schema::hasTable('admin_activity_logs')
            ? DB::table('admin_activity_logs as logs')->leftJoin('users as actors', 'actors.user_id', '=', 'logs.actor_user_id')
                ->orderByDesc('logs.created_at')->orderByDesc('logs.id')->limit(8)
                ->get(['logs.event', 'logs.summary', 'logs.created_at', 'logs.company_id', 'actors.name as actor_name'])
            : collect();

        $pendingTasks = collect();
        if (! $configuration->isEnabled() || $configuration->credentialSource() === 'none') {
            $pendingTasks->push(['title' => 'AI service needs configuration', 'detail' => 'Review the provider key and enablement state.', 'url' => auth()->user()->can('ai.settings.manage') ? route('admin.ai-configuration.index') : route('admin.ai-management.index')]);
        }
        if ($activeUserCount === null) {
            $pendingTasks->push(['title' => 'Account status tracking is being installed', 'detail' => 'Run the additive administrator workspace migration to enable active and inactive account status.', 'url' => route('roleaccess.index')]);
        }

        return view('admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'activeUsers' => $activeUserCount,
            'administratorCount' => User::query()->role('Administrator', 'web')->count(),
            'companyCount' => Company::query()->count(),
            'roleCount' => $roles->count(),
            'aiConfigured' => $configuration->credentialSource() !== 'none',
            'aiEnabled' => $configuration->isEnabled(),
            'aiModel' => $configuration->model(),
            'usage' => $usage,
            'recentActivity' => $recentActivity,
            'recentLogins' => $recentActivity->where('event', 'user.login')->take(5),
            'databaseHealthy' => $databaseHealthy,
            'pendingTasks' => $pendingTasks,
            'currentCompany' => auth()->user()->currentCompany(),
        ]);
    }

    public function permissionMatrix(Request $request, AiCapabilityRegistry $capabilities): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::in(['all', 'ai'])],
            'company' => ['nullable', Rule::in(['all', 'MI', 'MMC'])],
        ]);
        $scope = $validated['scope'] ?? 'all';
        $company = $validated['company'] ?? 'all';
        $permissions = Permission::query()->where('guard_name', 'web')
            ->when($scope === 'ai', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'ai.%')->orWhere('name', 'like', 'mi.%')->orWhere('name', 'like', 'mmc.%');
            }))
            ->when($company === 'MI', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'mi.%')->orWhere('name', 'like', 'ai.%');
            }))
            ->when($company === 'MMC', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'mmc.%')->orWhere('name', 'like', 'ai.%');
            }))
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->get();
        $roles = Role::query()->where('guard_name', 'web')->with(['permissions' => fn ($query) => $query->where('guard_name', 'web')])
            ->withCount('users')->orderBy('name')->get();
        $definitions = $capabilities->definitions();
        $permissionDetails = $permissions->mapWithKeys(function (Permission $permission) use ($definitions): array {
            $definition = collect($definitions)->first(function (array $definition) use ($permission): bool {
                if (in_array($permission->name, $definition['ai_permissions'] ?? [], true)) {
                    return true;
                }

                return collect($definition['business_permissions'] ?? [])->contains(
                    fn (string $requiredPermission): bool => in_array($permission->name, explode(' or ', $requiredPermission), true),
                );
            });
            if ($permission->name === 'ai.settings.manage') {
                return [$permission->getKey() => ['module' => 'AI', 'operation' => 'ADMIN', 'implementation' => 'implemented', 'handler' => 'AiConfigurationController', 'ai_permissions' => ['ai.settings.manage'], 'business_permissions' => []]];
            }

            return [$permission->getKey() => [
                'module' => str_starts_with($permission->name, 'mi.') ? 'MI' : (str_starts_with($permission->name, 'mmc.') ? 'MMC' : 'AI'),
                'operation' => $definition['operation'] ?? (str_starts_with($permission->name, 'ai.') ? 'CORE' : 'PERMISSION'),
                'implementation' => $definition['implementation'] ?? (str_starts_with($permission->name, 'ai.') ? 'implemented' : 'permission_only'),
                'handler' => $definition['handler'] ?? (str_starts_with($permission->name, 'ai.') ? 'AssistantController / AiAssistantService' : 'Existing module authorization'),
                'ai_permissions' => $definition['ai_permissions'] ?? [],
                'business_permissions' => $definition['business_permissions'] ?? [],
            ]];
        });

        return view('admin.access.permission-matrix', [
            'permissions' => $permissions,
            'roles' => $roles,
            'scope' => $scope,
            'companyScope' => $company,
            'search' => $validated['search'] ?? '',
            'permissionGroups' => $permissions->groupBy(fn (Permission $permission): string => $this->permissionGroup($permission->name)),
            'permissionDetails' => $permissionDetails,
            'companyLabels' => ['all' => 'All scopes', 'MI' => 'MI', 'MMC' => 'MMC'],
        ]);
    }

    public function aiPermissionMatrix(Request $request, AiCapabilityRegistry $capabilities): View
    {
        $request->merge(['scope' => 'ai']);

        return $this->permissionMatrix($request, $capabilities);
    }

    public function updatePermissionMatrix(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', Rule::in(['all', 'ai'])],
            'company' => ['required', Rule::in(['all', 'MI', 'MMC'])],
            'search' => ['nullable', 'string', 'max:100'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['required', Rule::in(['0', '1', 0, 1])],
            'confirm_critical' => ['nullable', 'accepted'],
        ]);

        $roleIds = array_map('intval', $validated['role_ids']);
        $roles = Role::query()->where('guard_name', 'web')->whereIn('id', $roleIds)->with('permissions')->get()->keyBy('id');
        if ($roles->count() !== count(array_unique($roleIds))) {
            abort(422, 'One or more roles are invalid for the web guard.');
        }

        $editablePermissions = $this->filteredPermissions($validated['scope'], $validated['company'], $validated['search'] ?? '')->get();
        $editableIds = $editablePermissions->modelKeys();
        $selected = $validated['permissions'] ?? [];
        $adminChanged = false;
        $adminSettingsPermission = Permission::query()->where('guard_name', 'web')->where('name', 'ai.settings.manage')->first();
        $changes = [];

        foreach ($roles as $role) {
            $currentIds = $role->permissions->modelKeys();
            $postedPermissions = $selected[$role->id] ?? [];
            $selectedIds = collect($postedPermissions)
                ->filter(fn ($value, $permissionId): bool => in_array((int) $permissionId, $editableIds, true) && (string) $value === '1')
                ->keys()->map(fn ($id): int => (int) $id)->all();
            if ($role->name !== 'Administrator' && $adminSettingsPermission !== null
                && ! in_array($adminSettingsPermission->getKey(), $currentIds, true)
                && in_array($adminSettingsPermission->getKey(), $selectedIds, true)) {
                return back()->withErrors(['permissions' => 'Only the Administrator role may receive AI configuration access.'])->withInput();
            }

            $newIds = array_values(array_unique(array_merge(array_diff($currentIds, $editableIds), $selectedIds)));
            sort($currentIds);
            sort($newIds);
            if ($currentIds === $newIds) {
                continue;
            }
            if ($role->name === 'Administrator') {
                $adminChanged = true;
                if ($adminSettingsPermission !== null && ! in_array($adminSettingsPermission->getKey(), $newIds, true)) {
                    return back()->withErrors(['permissions' => 'Keep ai.settings.manage assigned to Administrator so authorized system administrators retain configuration access.'])->withInput();
                }
            }
            $changes[] = ['role' => $role, 'before' => $currentIds, 'after' => $newIds];
        }

        if ($adminChanged && ! $request->boolean('confirm_critical')) {
            return back()->withErrors(['confirm_critical' => 'Confirm the Administrator role permission change before saving.'])->withInput();
        }

        DB::transaction(function () use ($changes, $audit, $request): void {
            foreach ($changes as $change) {
                $change['role']->syncPermissions(Permission::query()->where('guard_name', 'web')->whereIn('id', $change['after'])->get());
                $audit->record($request->user(), 'role.permissions_updated', 'role', $change['role']->getKey(), 'Permissions updated for '.$change['role']->name.'.', null, [
                    'permission_ids_before' => $change['before'],
                    'permission_ids_after' => $change['after'],
                ]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.access.permissions.index', array_filter([
            'scope' => $validated['scope'], 'company' => $validated['company'], 'search' => $validated['search'] ?? null,
        ]))->with('success', 'Role permissions saved.');
    }

    public function accessReview(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'integer', Rule::exists('companies', 'company_id')],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
        ]);
        $users = User::query()->with(['roles.permissions', 'permissions', 'companies'])
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('employee_id', 'like', '%'.$search.'%')->orWhere('department', 'like', '%'.$search.'%');
            }))
            ->when(isset($validated['company']), fn ($query) => $query->whereHas('companies', fn ($query) => $query->where('companies.company_id', $validated['company'])))
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->role($role, 'web'))
            ->when(($validated['status'] ?? 'all') !== 'all', fn ($query) => $query->where('is_active', ($validated['status'] ?? 'all') === 'active'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.access.user-review', [
            'users' => $users,
            'companies' => Company::query()->orderBy('name')->get(),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'filters' => $validated,
        ]);
    }

    public function aiOverview(AiProviderConfiguration $configuration, AiCapabilityRegistry $capabilities): View
    {
        $user = auth()->user();
        $company = $user->currentCompany();
        $capabilityGroups = $capabilities->groupedFor(
            $user,
            $company,
            $configuration->isEnabled(),
            $configuration->credentialSource() !== 'none',
        );

        $usage = Schema::hasTable('ai_activity_logs')
            ? DB::table('ai_activity_logs')->where('status', 'completed')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->selectRaw('COUNT(*) as request_count, COALESCE(SUM(COALESCE(input_tokens, 0)), 0) as input_tokens, COALESCE(SUM(COALESCE(output_tokens, 0)), 0) as output_tokens')->first()
            : (object) ['request_count' => null, 'input_tokens' => null, 'output_tokens' => null];
        $failures = Schema::hasTable('ai_activity_logs')
            ? DB::table('ai_activity_logs as activity')->leftJoin('users', 'users.user_id', '=', 'activity.user_id')
                ->leftJoin('companies', 'companies.company_id', '=', 'activity.company_id')->where('activity.status', 'failed')
                ->orderByDesc('activity.created_at')->limit(10)->get(['activity.model', 'activity.created_at', 'activity.company_id', 'companies.code as company_code', 'users.name as user_name'])
            : collect();

        return view('admin.ai-management', [
            'configurationReady' => $configuration->credentialSource() !== 'none',
            'enabled' => $configuration->isEnabled(),
            'credentialSource' => $configuration->credentialSource(),
            'model' => $configuration->model(),
            'company' => $company,
            'capabilityGroups' => $capabilityGroups,
            'usage' => $usage,
            'failures' => $failures,
        ]);
    }

    public function auditLog(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
        ]);
        $activities = DB::table('admin_activity_logs as logs')->leftJoin('users as actors', 'actors.user_id', '=', 'logs.actor_user_id')
            ->when($validated['search'] ?? null, fn (QueryBuilder $query, string $search) => $query->where(function (QueryBuilder $query) use ($search): void {
                $query->where('logs.summary', 'like', '%'.$search.'%')->orWhere('actors.name', 'like', '%'.$search.'%')->orWhere('logs.event', 'like', '%'.$search.'%');
            }))
            ->when($validated['event'] ?? null, fn (Builder $query, string $event) => $query->where('logs.event', $event))
            ->orderByDesc('logs.created_at')->orderByDesc('logs.id')
            ->paginate(30)->withQueryString();

        return view('admin.audit.index', ['activities' => $activities, 'filters' => $validated]);
    }

    private function filteredPermissions(string $scope, string $company, string $search): EloquentBuilder
    {
        return Permission::query()->where('guard_name', 'web')
            ->when($scope === 'ai', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'ai.%')->orWhere('name', 'like', 'mi.%')->orWhere('name', 'like', 'mmc.%');
            }))
            ->when($company === 'MI', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'mi.%')->orWhere('name', 'like', 'ai.%');
            }))
            ->when($company === 'MMC', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'mmc.%')->orWhere('name', 'like', 'ai.%');
            }))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
    }

    private function permissionGroup(string $permission): string
    {
        $prefix = explode('.', $permission)[0];

        return match ($prefix) {
            'ai' => match (explode('.', $permission)[1] ?? '') {
                'assistant', 'settings' => 'AI Core',
                'page-help' => 'AI Page Help',
                'business-search' => 'AI Business Search',
                default => 'AI '.ucfirst(str_replace(['-', '_'], ' ', explode('.', $permission)[1] ?? 'Assistant')),
            },
            'mi' => 'MI',
            'mmc' => 'MMC',
            default => ucfirst(str_replace(['-', '_'], ' ', $prefix)),
        };
    }
}
