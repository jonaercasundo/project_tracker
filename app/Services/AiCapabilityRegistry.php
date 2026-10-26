<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AiCapabilityRegistry
{
    /** @return array<int, array{id: string, label: string, subtitle: string, icon: string}> */
    public function categories(): array
    {
        return [
            ['id' => 'view_search', 'label' => 'View & Search', 'subtitle' => 'Find records across authorized modules', 'icon' => 'search'],
            ['id' => 'create_records', 'label' => 'Create Records', 'subtitle' => 'Prepare records and drafts with explicit confirmation', 'icon' => 'create'],
            ['id' => 'edit_update', 'label' => 'Edit & Update', 'subtitle' => 'Review changes and validate existing records', 'icon' => 'edit'],
            ['id' => 'pdf_generation', 'label' => 'Generate PDF', 'subtitle' => 'Produce authorized documents from existing templates', 'icon' => 'pdf'],
            ['id' => 'excel_tools', 'label' => 'Excel Tools', 'subtitle' => 'Import, export, validate and analyze workbooks', 'icon' => 'excel'],
            ['id' => 'charts_analytics', 'label' => 'Charts & Analytics', 'subtitle' => 'Explore verified metrics and comparisons', 'icon' => 'analytics'],
            ['id' => 'forecasting', 'label' => 'Forecasting', 'subtitle' => 'Estimate future trends from defined source data', 'icon' => 'forecast'],
            ['id' => 'document_intelligence', 'label' => 'Document Intelligence', 'subtitle' => 'Read and explain uploaded business documents', 'icon' => 'document'],
            ['id' => 'workflow_assistant', 'label' => 'Workflow Assistant', 'subtitle' => 'Guide users through existing approval workflows', 'icon' => 'workflow'],
            ['id' => 'proactive_assistance', 'label' => 'Proactive Assistance', 'subtitle' => 'Suggest relevant next steps based on verified results', 'icon' => 'proactive'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function definitions(): array
    {
        return [
            $this->capability('mi.catalog.search', 'view_search', 'MI Product Catalog Search', 'MI', 'READ', 'Search MI product names, SKU, category, materials and colors without exposing selling prices.', ['mi.product-catalog.view'], [], 'mi_catalog', 'AiReadOnlyToolGateway', ['search_mi_products', 'mi_product_catalog_summary', 'mi_products_needing_cop'], 'low', 'none', 'AI activity log'),
            $this->capability('mi.product_prices.lookup', 'view_search', 'MI Selling Price Lookup', 'MI', 'READ', 'Retrieve catalog selling prices only for accounts with the separate price permission.', ['mi.product-catalog.view', 'mi.product-prices.view'], [], 'mi_prices', 'AiReadOnlyToolGateway', ['lookup_mi_product_prices'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mmc.projects_deliveries.read', 'view_search', 'MMC Projects & Deliveries', 'MMC', 'READ', 'Search authorized MMC projects and inspect delivery counts for resolved project references.', [], ['user', 'Administrator'], 'mmc_operations', 'AiReadOnlyToolGateway', ['search_mmc_projects', 'get_mmc_project_delivery_summary', 'mmc_operations_snapshot', 'search_mmc_deliveries'], 'low', 'none', 'AI activity log'),
            $this->capability('mmc.inventory.search', 'view_search', 'MMC Inventory Search', 'MMC', 'READ', 'Search MMC warehouse inventory in the active authorized company.', [], ['user', 'Administrator', 'Warehouse_officer'], 'mmc_inventory', 'AiReadOnlyToolGateway', ['search_mmc_inventory'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.budget_requests.own_status', 'view_search', 'My MI Budget Requests', 'MI', 'READ', 'List only the authenticated MI employee’s own budget requests and their status.', [], ['user', 'Executive', 'executive'], 'mi_own_budget', 'AiReadOnlyToolGateway', ['my_mi_budget_requests'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.liquidations.own_status', 'view_search', 'My MI Liquidations', 'MI', 'READ', 'List the authenticated employee’s own liquidation titles and statuses without financial amounts.', ['mi.liquidation.view'], [], 'mi_liquidations', 'AiReadOnlyToolGateway', ['my_mi_liquidations'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.accounting.queue_summary', 'view_search', 'MI Accounting Queue Summary', 'MI', 'READ', 'Return accounting queue counts filtered by the user’s accounting permissions.', ['mi.accounting.dashboard.view'], ['Accounting', 'accounting'], 'mi_accounting', 'AiReadOnlyToolGateway', ['mi_accounting_queue_summary'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.approvals.pending_summary', 'view_search', 'MI Pending Approval Summary', 'MI', 'READ', 'Summarize only budget and travel approval queues the user may review.', ['mi.budget.approve or mi.travel.approve'], ['Executive', 'executive', 'Administrator'], 'mi_approvals', 'AiReadOnlyToolGateway', ['mi_pending_approval_summary'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.quotation.draft', 'create_records', 'MI Quotation Draft', 'MI', 'CREATE', 'Prepare and confirm an idempotent quotation draft using current authorized selling prices.', ['mi.quotation.create', 'mi.product-prices.view'], [], 'mi_quotation', 'AiQuotationWorkflowService', [], 'high', 'explicit_confirmation', 'Quotation workflow audit'),
            $this->capability('business.records.create', 'create_records', 'Create other business records', '*', 'CREATE', 'General conversational creation is not connected to existing application form services.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.records.update', 'edit_update', 'Edit existing business records', '*', 'UPDATE', 'No generic AI edit tool is implemented; edits must use each module’s existing form and validation.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.records.delete', 'edit_update', 'Delete or archive records', '*', 'DELETE / ARCHIVE', 'The assistant cannot delete or archive business records.', [], [], 'unavailable', null, [], 'critical', 'explicit_confirmation', 'Not available'),
            $this->capability('business.documents.pdf', 'pdf_generation', 'Generate business PDFs', '*', 'PDF GENERATION', 'AI-controlled PDF generation across products, quotations, finance and operations is not connected.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.workbooks', 'excel_tools', 'Import, export and analyze workbooks', '*', 'EXCEL IMPORT / EXPORT', 'Application workbook flows are separate; no AI workbook analysis or confirmed import/export tool is connected.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.charts', 'charts_analytics', 'Render charts and reports', '*', 'CHARTS / REPORTING', 'Some read tools return verified counts, but chart rendering and AI report generation are not implemented.', [], [], 'unavailable', 'AiReadOnlyToolGateway (verified metrics only)', [], 'moderate', 'none', 'Existing read-tool activity'),
            $this->capability('business.forecasts', 'forecasting', 'Statistical business forecasting', '*', 'FORECASTING / ANALYSIS', 'No deterministic forecasting service is connected to the assistant.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available'),
            $this->capability('business.document_intelligence', 'document_intelligence', 'Uploaded document intelligence', '*', 'DOCUMENT PROCESSING', 'The assistant does not ingest uploaded files or read their contents.', [], [], 'unavailable', null, [], 'sensitive_read', 'none', 'Not available'),
            $this->capability('business.workflow_guidance', 'workflow_assistant', 'Workflow guidance', '*', 'WORKFLOW EXECUTION', 'The assistant explains page context and can show authorized status summaries; it never submits or approves workflow items.', [], [], 'unavailable', 'AiModuleRegistry (page guidance only)', [], 'moderate', 'explicit_confirmation', 'AI activity log'),
            $this->capability('business.proactive_suggestions', 'proactive_assistance', 'Context-aware next steps', '*', 'WORKFLOW EXECUTION', 'Page context and the MI quotation prompt can suggest a relevant next step; general proactive workflows are not implemented.', [], [], 'unavailable', 'AiAssistantService (quotation suggestion only)', [], 'low', 'explicit_confirmation', 'AI activity log'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function groupedFor(User $user, ?Company $company, bool $enabled, bool $configured): array
    {
        $capabilities = collect($this->definitions())->map(function (array $capability) use ($user, $company, $enabled, $configured): array {
            $capability['permission_label'] = $capability['business_permissions'] === [] ? 'AI use + module access' : implode(', ', $capability['business_permissions']);
            $capability['status'] = $this->status($capability, $user, $company, $enabled, $configured);

            return $capability;
        })->groupBy('category');

        return collect($this->categories())->map(function (array $category) use ($capabilities): array {
            $items = $capabilities->get($category['id'], collect())->values();
            $category['capabilities'] = $items->all();
            $category['implemented_count'] = $items->whereIn('implementation', ['implemented', 'partial'])->count();
            $category['available_count'] = $items->whereIn('status', ['Available', 'Partially available'])->count();

            return $category;
        })->all();
    }

    public function allows(string $id, User $user, Company $company): bool
    {
        $capability = collect($this->definitions())->firstWhere('id', $id);
        if ($capability === null || $capability['implementation'] === 'unavailable' || ! $this->hasActiveCompany($user, $company)) {
            return false;
        }
        if ($capability['company'] !== '*' && $capability['company'] !== $company->code) {
            return false;
        }
        $businessPermissionsAreSatisfied = $capability['authorization_rule'] === 'mi_approvals'
            || $this->hasBusinessPermissions($user, $capability['business_permissions']);
        if (! $businessPermissionsAreSatisfied || ! $this->hasAnyRole($user, $capability['roles'])) {
            return false;
        }

        return match ($capability['authorization_rule']) {
            'mi_catalog' => $company->code === 'MI' && $user->can('mi.product-catalog.view'),
            'mi_prices' => $company->code === 'MI' && $user->can('mi.product-catalog.view') && $user->can('mi.product-prices.view'),
            'mi_quotation' => $company->code === 'MI' && $user->can('mi.quotation.create') && $user->can('mi.product-prices.view'),
            'mi_own_budget' => $company->code === 'MI' && $user->hasMIUserAccess(),
            'mi_liquidations' => $company->code === 'MI' && $user->can('mi.liquidation.view'),
            'mi_accounting' => $company->code === 'MI' && $user->hasAccountingRole() && $user->can('mi.accounting.dashboard.view'),
            'mi_approvals' => $company->code === 'MI' && ($user->canApproveMI('mi.budget.approve') || $user->canApproveMI('mi.travel.approve')),
            'mmc_operations' => $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator']),
            'mmc_inventory' => $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator', 'Warehouse_officer']),
            default => false,
        };
    }

    public function allowsTool(string $toolName, User $user, Company $company): bool
    {
        $capability = collect($this->definitions())->first(fn (array $item): bool => in_array($toolName, $item['tool_names'], true));

        return $capability !== null && $this->allows($capability['id'], $user, $company);
    }

    /** @param array<int, string> $permissions */
    private function hasBusinessPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (str_contains($permission, ' or ')) {
                if (! $user->hasAnyPermission(explode(' or ', $permission))) {
                    return false;
                }
            } elseif (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int, string> $roles */
    private function hasAnyRole(User $user, array $roles): bool
    {
        return $roles === [] || $user->hasAnyRole($roles);
    }

    private function hasActiveCompany(User $user, Company $company): bool
    {
        return $user->can('ai.assistant.use')
            && (bool) $company->is_active
            && $user->currentCompany()?->getKey() === $company->getKey()
            && $user->belongsToCompany($company->getKey());
    }

    /** @param array<string, mixed> $capability */
    private function status(array $capability, User $user, ?Company $company, bool $enabled, bool $configured): string
    {
        if ($capability['implementation'] === 'unavailable') {
            return 'Not yet implemented';
        }
        if (! $enabled) {
            return 'Disabled';
        }
        if (! $configured) {
            return 'AI configuration required';
        }
        if ($company === null) {
            return 'Select a company';
        }
        if ($capability['authorization_rule'] === 'mi_quotation'
            && (! Schema::hasTable('ai_quotation_workflows') || ! Schema::hasTable('ai_quotations'))) {
            return 'Quotation workflow unavailable';
        }
        if ($capability['company'] !== '*' && $capability['company'] !== $company->code) {
            return 'Select '.$capability['company'].' company';
        }
        if ($capability['implementation'] === 'partial' && $capability['authorization_rule'] === 'unavailable') {
            return 'Partially available';
        }
        if (! $this->allows($capability['id'], $user, $company)) {
            return $capability['permission_label'] ?? 'Permission or role required';
        }

        return $capability['implementation'] === 'partial' ? 'Partially available' : 'Available';
    }

    /** @param array<int, string> $businessPermissions
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $toolNames
     * @return array<string, mixed>
     */
    private function capability(string $id, string $category, string $label, string $company, string $operation, string $description, array $businessPermissions, array $roles, string $authorizationRule, ?string $handler, array $toolNames, string $risk, string $confirmation, string $audit): array
    {
        $implemented = $handler !== null;
        $partial = in_array($category, ['charts_analytics', 'workflow_assistant', 'proactive_assistance'], true);

        return [
            'id' => $id,
            'category' => $category,
            'label' => $label,
            'company' => $company,
            'operation' => $operation,
            'description' => $description,
            'business_permissions' => $businessPermissions,
            'roles' => $roles,
            'authorization_rule' => $authorizationRule,
            'handler' => $handler,
            'tool_names' => $toolNames,
            'input_schema' => $toolNames !== [] ? $this->toolSchemas($toolNames, true) : ($implemented ? ['type' => 'conversation', 'properties' => ['message' => 'user request', 'page_context' => 'route metadata only']] : ['status' => 'not_implemented']),
            'output_schema' => $toolNames !== [] ? $this->toolSchemas($toolNames, false) : ($implemented ? ['type' => 'object', 'properties' => ['answer' => 'assistant response grounded in authorized results']] : ['status' => 'not_implemented']),
            'risk' => $risk,
            'confirmation' => $confirmation,
            'audit' => $audit,
            'implementation' => $implemented ? ($partial ? 'partial' : 'implemented') : 'unavailable',
        ];
    }

    /** @param array<int, string> $toolNames
     * @return array<string, array<string, mixed>>
     */
    private function toolSchemas(array $toolNames, bool $input): array
    {
        $schemas = [];
        foreach ($toolNames as $toolName) {
            if ($input) {
                $schemas[$toolName] = match ($toolName) {
                    'search_mi_products', 'lookup_mi_product_prices', 'search_mmc_projects', 'search_mmc_deliveries', 'search_mmc_inventory' => [
                        'type' => 'object', 'properties' => ['query' => ['type' => 'string', 'maxLength' => 120]], 'required' => ['query'], 'additionalProperties' => false,
                    ],
                    'get_mmc_project_delivery_summary' => [
                        'type' => 'object',
                        'properties' => ['project_references' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1, 'maxItems' => 5], 'date_from' => ['type' => ['string', 'null']], 'date_to' => ['type' => ['string', 'null']]],
                        'required' => ['project_references', 'date_from', 'date_to'], 'additionalProperties' => false,
                    ],
                    default => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false],
                };

                continue;
            }

            $schemas[$toolName] = match ($toolName) {
                'search_mi_products' => ['type' => 'array', 'items' => ['id' => 'integer', 'name' => 'string', 'sku' => 'string|null', 'category' => 'string|null', 'materials' => 'string|null', 'colors' => 'string|null']],
                'lookup_mi_product_prices' => ['type' => 'array', 'items' => ['id' => 'integer', 'name' => 'string', 'sku' => 'string|null', 'selling_price' => 'number|null', 'currency' => 'string']],
                'mi_product_catalog_summary' => ['type' => 'object', 'properties' => ['product_count' => 'integer', 'products_missing_cop' => 'integer|null']],
                'mi_products_needing_cop' => ['type' => 'array', 'items' => ['id' => 'integer', 'name' => 'string', 'sku' => 'string|null']],
                'get_mmc_project_delivery_summary' => ['type' => 'object', 'properties' => ['projects' => 'array of verified project delivery summaries']],
                'mmc_operations_snapshot' => ['type' => 'object', 'properties' => ['projects' => 'verified aggregate', 'deliveries' => 'verified aggregate', 'inventory' => 'verified aggregate']],
                'my_mi_budget_requests' => ['type' => 'array', 'items' => ['control_id' => 'string', 'status' => 'string', 'budget_total' => 'number']],
                'my_mi_liquidations' => ['type' => 'array', 'items' => ['type' => 'string', 'reference' => 'string|null', 'date' => 'string|null', 'status' => 'string']],
                'mi_accounting_queue_summary' => ['type' => 'object', 'properties' => ['queues' => 'permission-filtered counts']],
                'mi_pending_approval_summary' => ['type' => 'object', 'properties' => ['pending_budget_approvals' => 'integer', 'pending_travel_liquidations' => 'integer']],
                default => ['type' => 'array', 'items' => ['fields' => 'authorized module result']],
            };
        }

        return $schemas;
    }
}
