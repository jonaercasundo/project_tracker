<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiCapabilityPermissionSeeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AiPermissionCatalog
{
    private const CATEGORY_LABELS = [
        'core_ai' => 'Core AI',
        'mmc_intelligence' => 'MMC Intelligence',
        'mi_intelligence' => 'MI Intelligence',
        'ai_business_actions' => 'AI Business Actions',
        'documents_analytics' => 'Documents & Analytics',
    ];

    /** @return array<int, string> */
    public function names(AiCapabilityRegistry $registry): array
    {
        return $this->namesFromDefinitions($registry->definitions());
    }

    /** @param array<int, array<string, mixed>> $definitions
     * @return array<int, string>
     */
    private function namesFromDefinitions(array $definitions): array
    {
        return collect(AiAssistantPermissionSeeder::permissions())
            ->merge(AiCapabilityPermissionSeeder::permissions())
            ->merge(collect($definitions)->flatMap(fn (array $definition): array => $definition['ai_permissions'] ?? []))
            ->filter(fn (mixed $permission): bool => is_string($permission) && str_starts_with($permission, 'ai.'))
            ->unique()->sort()->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function entries(AiCapabilityRegistry $registry, ?User $user = null, ?Role $role = null, ?Company $company = null, ?array $capabilityDefinitions = null): array
    {
        $capabilityDefinitions ??= $registry->definitions();
        $definitions = collect($capabilityDefinitions);
        $permissionNames = $this->namesFromDefinitions($capabilityDefinitions);
        $permissions = Permission::query()->where('guard_name', 'web')->whereIn('name', $permissionNames)->get()->keyBy('name');
        $directNames = $user?->permissions->pluck('name')->all() ?? [];
        $roleNames = $user?->roles->flatMap(fn (Role $assignedRole): Collection => $assignedRole->permissions->pluck('name'))->unique()->values()->all() ?? [];
        $selectedRoleNames = $role?->permissions->pluck('name')->all() ?? [];
        $categoryAliases = [
            'page_help' => 'core_ai', 'view_search' => 'mi_intelligence',
            'create_records' => 'ai_business_actions', 'edit_update' => 'ai_business_actions',
            'pdf_generation' => 'documents_analytics', 'excel_tools' => 'documents_analytics',
            'charts_analytics' => 'documents_analytics', 'forecasting' => 'documents_analytics',
            'document_intelligence' => 'documents_analytics', 'workflow_guidance' => 'core_ai',
            'proactive_assistance' => 'core_ai',
        ];

        return collect($permissionNames)->map(function (string $name) use ($definitions, $permissions, $directNames, $roleNames, $selectedRoleNames, $user, $role, $company, $categoryAliases): array {
            $related = $definitions->filter(fn (array $definition): bool => in_array($name, $definition['ai_permissions'] ?? [], true))->values();
            $labels = $related->pluck('label')->unique()->values()->all();
            $categories = $related->map(fn (array $definition): string => $categoryAliases[$definition['category']] ?? $definition['category'])->unique()->values()->all();
            $scopes = $related->pluck('company')->unique()->values()->all();
            $sensitive = $related->contains(fn (array $definition): bool => in_array($definition['risk'], ['critical', 'high'], true));
            $direct = $role !== null
                ? in_array($name, $selectedRoleNames, true)
                : in_array($name, $directNames, true);
            $inherited = $role !== null ? false : in_array($name, $roleNames, true);
            $granted = $role !== null ? $direct : ($direct || $inherited);
            $scopeMatches = $company === null || $scopes === [] || in_array('*', $scopes, true) || in_array($company->code, $scopes, true);
            $category = in_array($name, ['ai.assistant.use', 'ai.business-search.use', 'ai.page-help.use'], true)
                ? 'core_ai'
                : ($categories[0] ?? $this->categoryForName($name));
            $inheritedRoles = $user?->roles->filter(fn (Role $assignedRole): bool => $assignedRole->permissions->contains('name', $name))->pluck('name')->values()->all() ?? [];
            $inheritedRoleIds = $user?->roles->filter(fn (Role $assignedRole): bool => $assignedRole->permissions->contains('name', $name))->pluck('id')->values()->all() ?? [];

            return [
                'name' => $name,
                'description' => $labels === [] ? 'Predefined AI permission used by an existing assistant workflow.' : 'Controls access to '.implode(', ', $labels).'.',
                'capabilities' => $labels,
                'capability_ids' => $related->pluck('id')->values()->all(),
                'categories' => $categories === [] ? [$category] : $categories,
                'scopes' => $scopes === [] ? [$this->scopeForName($name)] : $scopes,
                'registered' => $permissions->has($name),
                'permission_id' => $permissions->get($name)?->getKey(),
                'granted' => $granted,
                'direct' => $direct,
                'inherited' => $inherited,
                'inherited_roles' => $inheritedRoles,
                'inherited_role_ids' => $inheritedRoleIds,
                'source' => $role !== null ? ($direct ? 'Assigned to selected role' : 'Missing from selected role') : ($direct && $inherited ? 'Direct user permission and inherited role' : ($direct ? 'Direct user permission' : ($inherited ? 'Inherited role permission' : 'Missing'))),
                'implementation_available' => $related->contains(fn (array $definition): bool => $definition['implementation'] !== 'unavailable'),
                'sensitive' => $sensitive || in_array($name, ['ai.assistant.use', 'ai.business-search.use', 'ai.mi.prices.lookup', 'ai.mmc.financial.analyze'], true),
                'scope_matches' => $scopeMatches,
                'group' => self::CATEGORY_LABELS[$category] ?? 'Core AI',
            ];
        })->all();
    }

    private function categoryForName(string $name): string
    {
        if (str_starts_with($name, 'ai.mmc.')) {
            return 'mmc_intelligence';
        }
        if (str_starts_with($name, 'ai.mi.')) {
            return 'mi_intelligence';
        }
        if (str_starts_with($name, 'ai.records.')) {
            return 'ai_business_actions';
        }
        if (str_starts_with($name, 'ai.documents.') || str_starts_with($name, 'ai.excel.') || str_starts_with($name, 'ai.attachments.') || str_starts_with($name, 'ai.charts.') || str_starts_with($name, 'ai.data.')) {
            return 'documents_analytics';
        }

        return 'core_ai';
    }

    private function scopeForName(string $name): string
    {
        return str_starts_with($name, 'ai.mmc.') ? 'MMC' : (str_starts_with($name, 'ai.mi.') ? 'MI' : 'Global');
    }
}
