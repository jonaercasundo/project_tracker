<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AiCapabilityRegistry;
use App\Services\AiPermissionCatalog;
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

    public function permissionMatrix(Request $request, AiCapabilityRegistry $capabilities, AiPermissionCatalog $permissionCatalog): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::in(['all', 'ai'])],
            'company' => ['nullable', Rule::in(['all', 'MI', 'MMC'])],
            'module' => ['nullable', Rule::in(['all', 'AI', 'MI', 'MMC'])],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'company_id')],
            'diagnostic_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')],
            'diagnostic_capability_id' => ['nullable', 'string', 'max:120'],
            'catalog_subject_type' => ['nullable', Rule::in(['none', 'user', 'role'])],
            'catalog_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')],
            'catalog_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'catalog_capability_id' => ['nullable', 'string', 'max:120'],
            'catalog_status' => ['nullable', Rule::in(['all', 'missing', 'granted', 'not-registered', 'needs-attention'])],
        ]);
        $scope = $validated['scope'] ?? 'all';
        $company = $validated['company'] ?? 'all';
        $module = $validated['module'] ?? 'all';
        $permissionQuery = Permission::query()->where('guard_name', 'web')
            ->when($scope === 'ai', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'ai.%')->orWhere('name', 'like', 'mi.%')->orWhere('name', 'like', 'mmc.%');
            }))
            ->when($company === 'MI', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'mi.%')->orWhere('name', 'like', 'ai.%');
            }))
            ->when($company === 'MMC', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', 'mmc.%')->orWhere('name', 'like', 'ai.%');
            }))
            ->when($module !== 'all', function ($query) use ($module): void {
                $query->where(function ($query) use ($module): void {
                    if ($module === 'MMC') {
                        $query->where('name', 'like', 'mmc.%')->orWhere('name', 'like', 'ai.mmc.%');
                    } elseif ($module === 'MI') {
                        $query->where('name', 'like', 'mi.%')->orWhere('name', 'like', 'ai.mi.%');
                    } else {
                        $query->where('name', 'like', 'ai.%')->where('name', 'not like', 'ai.mmc.%')->where('name', 'not like', 'ai.mi.%');
                    }
                });
            })
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'));
        $permissions = $permissionQuery->orderBy('name')->get();
        $roles = Role::query()->where('guard_name', 'web')->with(['permissions' => fn ($query) => $query->where('guard_name', 'web')])
            ->withCount('users')->orderBy('name')->get();
        $diagnosticUser = isset($validated['diagnostic_user_id'])
            ? User::query()->with(['roles.permissions', 'permissions', 'companies'])->findOrFail($validated['diagnostic_user_id'])
            : auth()->user();
        $catalogSubjectType = $validated['catalog_subject_type'] ?? 'none';
        $catalogUser = $catalogSubjectType === 'user' && isset($validated['catalog_user_id'])
            ? User::query()->with(['roles.permissions', 'permissions'])->findOrFail($validated['catalog_user_id'])
            : null;
        $catalogRole = $catalogSubjectType === 'role' && isset($validated['catalog_role_id'])
            ? Role::query()->where('guard_name', 'web')->with('permissions')->findOrFail($validated['catalog_role_id'])
            : null;
        $diagnosticCompany = isset($validated['company_id'])
            ? Company::query()->findOrFail($validated['company_id'])
            : $diagnosticUser->companies()->where('companies.is_active', true)->orderBy('companies.company_id')->first();
        $aiConfiguration = app(AiProviderConfiguration::class);
        $aiEnabled = $aiConfiguration->isEnabled();
        $aiConfigured = $aiConfiguration->credentialSource() !== 'none';
        $definitions = $capabilities->definitions();
        $selectedCapability = collect($definitions)->firstWhere('id', $validated['diagnostic_capability_id'] ?? null);
        $selectedCapabilityDiagnostic = $selectedCapability !== null
            ? $capabilities->diagnostic($selectedCapability['id'], $diagnosticUser, $diagnosticCompany, $aiEnabled, $aiConfigured)
            : null;
        $permissionDetails = $permissions->mapWithKeys(function (Permission $permission) use ($definitions, $capabilities, $diagnosticUser, $diagnosticCompany, $aiEnabled, $aiConfigured): array {
            $definition = collect($definitions)->first(function (array $definition) use ($permission): bool {
                if (in_array($permission->name, $definition['ai_permissions'] ?? [], true)) {
                    return true;
                }

                return collect($definition['business_permissions'] ?? [])->contains(
                    fn (string $requiredPermission): bool => in_array($permission->name, explode(' or ', $requiredPermission), true),
                );
            });
            $diagnostic = $definition !== null
                ? $capabilities->diagnostic($definition['id'], $diagnosticUser, $diagnosticCompany, $aiEnabled, $aiConfigured)
                : ['status' => 'Permission only', 'reason' => 'This existing business permission is enforced by its module; it does not enable an AI tool by itself.', 'allowed' => false, 'required_ai_permissions' => [], 'granted_ai_permissions' => [], 'missing_ai_permissions' => [], 'business_policy_requirements' => []];

            return [$permission->getKey() => [
                'module' => $definition !== null && in_array($definition['company'], ['MI', 'MMC'], true)
                    ? $definition['company']
                    : (str_starts_with($permission->name, 'mi.') ? 'MI' : (str_starts_with($permission->name, 'mmc.') ? 'MMC' : 'AI')),
                'operation' => $definition['operation'] ?? (str_starts_with($permission->name, 'ai.') ? 'CORE' : 'PERMISSION'),
                'implementation' => $definition['implementation'] ?? (str_starts_with($permission->name, 'ai.') ? 'implemented' : 'permission_only'),
                'implementation_status' => $definition['implementation_status'] ?? (str_starts_with($permission->name, 'ai.') ? 'Implemented' : 'Permission only'),
                'company_scope' => $definition['company'] ?? (str_starts_with($permission->name, 'mi.') ? 'MI' : (str_starts_with($permission->name, 'mmc.') ? 'MMC' : '*')),
                'handler' => $definition['handler'] ?? (str_starts_with($permission->name, 'ai.') ? 'AssistantController / AiAssistantService' : 'Existing module authorization'),
                'ai_permissions' => $definition['ai_permissions'] ?? [],
                'business_permissions' => $definition['business_permissions'] ?? [],
                'required_ai_permissions' => $diagnostic['required_ai_permissions'],
                'granted_ai_permissions' => $diagnostic['granted_ai_permissions'],
                'missing_ai_permissions' => $diagnostic['missing_ai_permissions'],
                'business_policy_requirements' => $diagnostic['business_policy_requirements'],
                'failed_business_checks' => $diagnostic['failed_business_checks'] ?? [],
                'capability' => $definition['label'] ?? null,
                'risk' => $definition['risk'] ?? 'business permission',
                'status' => $diagnostic,
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
            'moduleFilter' => $module,
            'companies' => Company::query()->orderBy('name')->get(),
            'diagnosticUsers' => User::query()->orderBy('name')->limit(300)->get(['user_id', 'name', 'email']),
            'diagnosticUser' => $diagnosticUser,
            'diagnosticCompany' => $diagnosticCompany,
            'capabilityGroups' => $capabilities->groupedFor($diagnosticUser, $diagnosticCompany, $aiEnabled, $aiConfigured),
            'aiEnabled' => $aiEnabled,
            'aiConfigured' => $aiConfigured,
            'aiPermissions' => Permission::query()->where('guard_name', 'web')->where('name', 'like', 'ai.%')->where('name', '!=', 'ai.settings.manage')->orderBy('name')->get(),
            'permissionCatalog' => $permissionCatalog->entries($capabilities, $catalogUser, $catalogRole, $diagnosticCompany, $definitions),
            'catalogSubjectType' => $catalogSubjectType,
            'catalogUser' => $catalogUser,
            'catalogRole' => $catalogRole,
            'catalogCapabilityId' => $validated['catalog_capability_id'] ?? '',
            'catalogStatusFilter' => $validated['catalog_status'] ?? 'all',
            'diagnosticCapabilityId' => $validated['diagnostic_capability_id'] ?? '',
            'selectedCapability' => $selectedCapability,
            'selectedCapabilityDiagnostic' => $selectedCapabilityDiagnostic,
            'diagnosticCapabilities' => collect($definitions)->map(fn (array $definition): array => ['id' => $definition['id'], 'label' => $definition['label'], 'company' => $definition['company']])->all(),
        ]);
    }

    public function aiPermissionMatrix(Request $request, AiCapabilityRegistry $capabilities, AiPermissionCatalog $permissionCatalog): View
    {
        $request->merge(['scope' => 'ai']);

        return $this->permissionMatrix($request, $capabilities, $permissionCatalog);
    }

    public function configureAiPermission(Request $request, AiCapabilityRegistry $capabilities, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', Rule::in(['user', 'role'])],
            'user_subject_id' => ['nullable', 'required_if:subject_type,user', 'integer', Rule::exists('users', 'user_id')],
            'role_subject_id' => ['nullable', 'required_if:subject_type,role', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'permission_id' => ['nullable', 'integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
            'permission_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'permission_ids.*' => ['required', 'integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
            'action' => ['required', Rule::in(['grant', 'revoke'])],
            'confirm_shared_role' => ['nullable', 'boolean'],
            'confirm_changes' => ['nullable', 'boolean'],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'company_id')],
            'diagnostic_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')],
            'diagnostic_capability_id' => ['nullable', 'string', 'max:120'],
            'catalog_capability_id' => ['nullable', 'string', 'max:120'],
            'catalog_status' => ['nullable', Rule::in(['all', 'missing', 'granted', 'not-registered', 'needs-attention'])],
        ]);

        $permissionIds = array_values(array_unique(array_map('intval', $validated['permission_ids'] ?? (isset($validated['permission_id']) ? [$validated['permission_id']] : []))));
        abort_if($permissionIds === [], 422, 'Select at least one AI permission.');
        $permissions = Permission::query()->where('guard_name', 'web')->whereIn('id', $permissionIds)->get();
        abort_if($permissions->count() !== count($permissionIds), 422, 'One or more selected permissions are invalid for the web guard.');
        $allowedNames = app(AiPermissionCatalog::class)->names($capabilities);
        abort_unless($permissions->every(fn (Permission $permission): bool => in_array($permission->name, $allowedNames, true) && $permission->name !== 'ai.settings.manage'), 422, 'Only predefined, non-configuration AI permissions can be changed here.');
        $sensitivePermissionNames = collect($capabilities->definitions())
            ->filter(fn (array $definition): bool => in_array($definition['risk'], ['critical', 'high'], true))
            ->flatMap(fn (array $definition): array => $definition['ai_permissions'] ?? [])
            ->unique()->values()->all();
        $requiresConfirmation = count($permissions) > 1 || $permissions->contains(fn (Permission $permission): bool => in_array($permission->name, $sensitivePermissionNames, true));
        abort_if($requiresConfirmation && ! $request->boolean('confirm_changes'), 422, 'Confirm the sensitive or bulk AI permission change before saving.');
        $actor = $request->user();
        if ($validated['subject_type'] === 'user') {
            $subject = User::query()->findOrFail($validated['user_subject_id'] ?? null);
            abort_if($subject->is($actor), 422, 'Administrators cannot grant or revoke AI permissions on their own account.');
            $subjectLabel = $subject->name.' ('.$subject->email.')';
            $relation = $subject;
            $verificationUser = $subject;
            if ($validated['action'] === 'revoke') {
                $directPermissionNames = $subject->permissions()->whereIn('permissions.id', $permissionIds)->pluck('permissions.name')->all();
                $notDirectlyAssigned = $permissions->filter(fn (Permission $permission): bool => ! in_array($permission->name, $directPermissionNames, true));
                abort_if($notDirectlyAssigned->isNotEmpty(), 422, 'Some selected permissions are not directly assigned to this user. Remove inherited permissions from the relevant role instead.');
            }
        } else {
            $relation = Role::query()->where('guard_name', 'web')->findOrFail($validated['role_subject_id'] ?? null);
            abort_if($relation->name === 'Administrator', 422, 'The protected Administrator role must be managed through the role matrix.');
            abort_if($validated['action'] === 'grant' && $actor->hasRole($relation), 422, 'Administrators cannot grant AI permissions to themselves through a shared role.');
            if (! $request->boolean('confirm_shared_role')) {
                return back()->withErrors(['confirm_shared_role' => 'Confirm this change to a shared role before saving.'])->withInput();
            }
            if ($validated['action'] === 'revoke') {
                $notAssigned = $permissions->filter(fn (Permission $permission): bool => ! $relation->hasPermissionTo($permission));
                abort_if($notAssigned->isNotEmpty(), 422, 'One or more selected AI permissions are not directly assigned to this role.');
            }
            $subjectLabel = $relation->name.' role ('.$relation->users()->count().' users)';
            $verificationUser = $relation->users()->orderBy('name')->first();
        }

        if ($validated['action'] === 'grant') {
            $alreadyGranted = $permissions->first(fn (Permission $permission): bool => $validated['subject_type'] === 'user'
                ? $relation->can($permission->name)
                : $relation->hasPermissionTo($permission));
            abort_if($alreadyGranted !== null, 422, 'One or more selected AI permissions are already effective. Review the direct or role grant source before changing access.');
        }

        DB::transaction(function () use ($relation, $permissions, $validated, $audit, $actor, $subjectLabel): void {
            foreach ($permissions as $permission) {
                if ($validated['action'] === 'grant') {
                    $relation->givePermissionTo($permission);
                } else {
                    $relation->revokePermissionTo($permission);
                }
                $event = $validated['action'] === 'grant' ? 'ai.permission_granted' : 'ai.permission_revoked';
                $audit->record($actor, $event, $validated['subject_type'], $relation->getKey(), ucfirst($validated['action']).' '.$permission->name.' for '.$subjectLabel.'.', null, [
                    'permission' => $permission->name,
                    'subject_type' => $validated['subject_type'],
                    'action' => $validated['action'],
                    'preview_company_id' => $validated['company_id'] ?? null,
                ]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $verificationCompanyId = isset($validated['company_id']) && $verificationUser?->belongsToCompany($validated['company_id'])
            ? (int) $validated['company_id']
            : $verificationUser?->companies()->where('companies.is_active', true)->orderBy('companies.company_id')->value('companies.company_id');

        return redirect()->route('admin.access.permissions.ai', array_filter([
            'company_id' => $verificationCompanyId,
            'diagnostic_user_id' => $verificationUser?->getKey() ?? $validated['diagnostic_user_id'] ?? null,
            'diagnostic_capability_id' => $validated['diagnostic_capability_id'] ?? null,
            'catalog_subject_type' => $validated['subject_type'] === 'role' ? 'role' : 'user',
            'catalog_user_id' => $validated['subject_type'] === 'user' ? $verificationUser?->getKey() : null,
            'catalog_role_id' => $validated['subject_type'] === 'role' ? $relation->getKey() : null,
            'catalog_capability_id' => $validated['catalog_capability_id'] ?? null,
            'catalog_status' => $validated['catalog_status'] ?? null,
        ]))->with('success', 'AI permission change saved. Effective access has been refreshed; review the updated diagnostics below.');
    }

    public function registerAiPermission(Request $request, AiCapabilityRegistry $capabilities, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'permission_name' => ['required', 'string', 'max:191'],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'company_id')],
            'diagnostic_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')],
            'diagnostic_capability_id' => ['nullable', 'string', 'max:120'],
            'catalog_subject_type' => ['nullable', Rule::in(['none', 'user', 'role'])],
            'catalog_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')],
            'catalog_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'catalog_capability_id' => ['nullable', 'string', 'max:120'],
            'catalog_status' => ['nullable', Rule::in(['all', 'missing', 'granted', 'not-registered', 'needs-attention'])],
        ]);
        $permissionName = $validated['permission_name'];
        abort_unless(in_array($permissionName, app(AiPermissionCatalog::class)->names($capabilities), true), 422, 'Only predefined AI permissions can be registered.');

        $wasRegistered = Permission::query()->where('name', $permissionName)->where('guard_name', 'web')->exists();
        $permission = DB::transaction(function () use ($permissionName, $request, $audit, $wasRegistered): Permission {
            $permission = Permission::findOrCreate($permissionName, 'web');
            if (! $wasRegistered) {
                $audit->record($request->user(), 'ai.permission_registered', 'permission', $permission->getKey(), 'Registered predefined AI permission '.$permissionName.'.', null, [
                    'permission' => $permissionName,
                    'guard_name' => 'web',
                    'granted' => false,
                ]);
            }

            return $permission;
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.access.permissions.ai', array_filter($validated))->with('success', $wasRegistered ? 'Permission is already registered for the web guard. No access was changed.' : 'Permission registered for the web guard. It has not been granted to anyone.');
    }

    public function updatePermissionMatrix(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', Rule::in(['all', 'ai'])],
            'company' => ['required', Rule::in(['all', 'MI', 'MMC'])],
            'search' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', Rule::in(['all', 'AI', 'MI', 'MMC'])],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'company_id')],
            'diagnostic_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')],
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

        $editablePermissions = $this->filteredPermissions($validated['scope'], $validated['company'], $validated['search'] ?? '', $validated['module'] ?? 'all')->get();
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
            'module' => $validated['module'] ?? 'all', 'company_id' => $validated['company_id'] ?? null,
            'diagnostic_user_id' => $validated['diagnostic_user_id'] ?? null,
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

    private function filteredPermissions(string $scope, string $company, string $search, string $module = 'all'): EloquentBuilder
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
            ->when($module !== 'all', function ($query) use ($module): void {
                $query->where(function ($query) use ($module): void {
                    if ($module === 'MMC') {
                        $query->where('name', 'like', 'mmc.%')->orWhere('name', 'like', 'ai.mmc.%');
                    } elseif ($module === 'MI') {
                        $query->where('name', 'like', 'mi.%')->orWhere('name', 'like', 'ai.mi.%');
                    } else {
                        $query->where('name', 'like', 'ai.%')->where('name', 'not like', 'ai.mmc.%')->where('name', 'not like', 'ai.mi.%');
                    }
                });
            })
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
