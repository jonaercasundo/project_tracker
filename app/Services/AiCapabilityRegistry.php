<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ProjectInformation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AiCapabilityRegistry
{
    /** @return array<int, array{id: string, label: string, subtitle: string, icon: string}> */
    public function categories(): array
    {
        return [
            ['id' => 'core_ai', 'label' => 'Core AI', 'subtitle' => 'Assistant access, page guidance, configuration, and conversation history', 'icon' => 'help'],
            ['id' => 'mmc_intelligence', 'label' => 'MMC Intelligence', 'subtitle' => 'Project, delivery, inventory, reporting, and analysis capabilities', 'icon' => 'search'],
            ['id' => 'mi_intelligence', 'label' => 'MI Intelligence', 'subtitle' => 'Product search, pricing, quotations, employee records, and accounting', 'icon' => 'create'],
            ['id' => 'ai_business_actions', 'label' => 'AI Business Actions', 'subtitle' => 'View and change business records through authorized module workflows', 'icon' => 'edit'],
            ['id' => 'documents_analytics', 'label' => 'Documents & Analytics', 'subtitle' => 'Documents, workbooks, charts, and forecasting', 'icon' => 'analytics'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function definitions(): array
    {
        return [
            $this->capability('ai.page_help', 'page_help', 'Page-aware help', '*', 'READ', 'Explain page purpose, visible workflow guidance, and links using route metadata only.', [], [], 'page_help', 'AiModuleRegistry', [], 'low', 'none', 'AI activity log'),
            $this->capability('mi.catalog.search', 'view_search', 'MI Product Catalog Search', 'MI', 'READ', 'Search MI product names, SKU, category, materials and colors without exposing selling prices.', ['mi.product-catalog.view'], [], 'mi_catalog', 'AiReadOnlyToolGateway', ['search_mi_products', 'mi_product_catalog_summary', 'mi_products_needing_cop'], 'low', 'none', 'AI activity log'),
            $this->capability('mi.product_prices.lookup', 'view_search', 'MI Selling Price Lookup', 'MI', 'READ', 'Retrieve catalog selling prices only for accounts with the separate price permission.', ['mi.product-catalog.view', 'mi.product-prices.view'], [], 'mi_prices', 'AiReadOnlyToolGateway', ['lookup_mi_product_prices'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mmc.projects.view', 'mmc_intelligence', 'View MMC Projects', 'MMC', 'READ', 'Search projects using the existing MMC project business policy.', [], [], 'mmc_projects', 'AiReadOnlyToolGateway', ['search_mmc_projects'], 'low', 'none', 'AI activity log'),
            $this->capability('mmc.deliveries.analyze', 'mmc_intelligence', 'View Delivery Status & Analyze Counts', 'MMC', 'READ / ANALYZE', 'Resolve project references and calculate delivery counts using the MMC dashboard service.', [], [], 'mmc_delivery_analysis', 'AiReadOnlyToolGateway', ['get_mmc_project_delivery_summary'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mmc.deliveries.search', 'mmc_intelligence', 'Search MMC Deliveries', 'MMC', 'READ', 'Search authorized MMC delivery records.', [], [], 'mmc_delivery_search', 'AiReadOnlyToolGateway', ['search_mmc_deliveries'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mmc.operations.snapshot', 'mmc_intelligence', 'MMC Operations Snapshot', 'MMC', 'READ / ANALYZE', 'Return project, delivery, and inventory aggregates when each source is authorized.', [], [], 'mmc_operations_snapshot', 'AiReadOnlyToolGateway', ['mmc_operations_snapshot'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mmc.inventory.search', 'mmc_intelligence', 'Search MMC Inventory', 'MMC', 'READ', 'Search MMC warehouse inventory under its existing business authorization.', [], [], 'mmc_inventory', 'AiReadOnlyToolGateway', ['search_mmc_inventory'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.budget_requests.own_status', 'mi_intelligence', 'My MI Budget Requests', 'MI', 'READ', 'List only the authenticated MI employee’s own budget requests and their status.', [], [], 'mi_own_budget', 'AiReadOnlyToolGateway', ['my_mi_budget_requests'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.liquidations.own_status', 'view_search', 'My MI Liquidations', 'MI', 'READ', 'List the authenticated employee’s own liquidation titles and statuses without financial amounts.', ['mi.liquidation.view'], [], 'mi_liquidations', 'AiReadOnlyToolGateway', ['my_mi_liquidations'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.accounting.queue_summary', 'mi_intelligence', 'MI Accounting Queue Summary', 'MI', 'READ', 'Return accounting queue counts filtered by the user’s accounting permissions.', ['mi.accounting.dashboard.view'], [], 'mi_accounting', 'AiReadOnlyToolGateway', ['mi_accounting_queue_summary'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.approvals.pending_summary', 'mi_intelligence', 'MI Pending Approval Summary', 'MI', 'READ', 'Summarize only budget and travel approval queues the user may review.', ['mi.budget.approve or mi.travel.approve'], [], 'mi_approvals', 'AiReadOnlyToolGateway', ['mi_pending_approval_summary'], 'sensitive_read', 'none', 'AI activity log'),
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
            $this->capability('ai.configuration', 'core_ai', 'AI Configuration', 'AI', 'ADMIN', 'Manage system-wide AI provider settings.', [], [], 'ai_settings', 'AiConfigurationController', [], 'high', 'explicit_confirmation', 'Administrator audit log', ['ai.settings.manage']),
            $this->capability('ai.conversations', 'core_ai', 'Conversation History', '*', 'READ / DELETE OWN', 'Read and delete only the authenticated user’s own conversations.', [], [], 'conversation_history', 'AssistantController', [], 'low', 'none', 'AI activity log', ['ai.assistant.use']),
            $this->capability('mmc.reports.generate', 'mmc_intelligence', 'Generate MMC Reports', 'MMC', 'REPORT', 'AI-generated MMC reports are not connected.', [], [], 'unavailable', null, [], 'high', 'none', 'Not available', ['ai.mmc.reports.generate']),
            $this->capability('mmc.charts.generate', 'mmc_intelligence', 'Generate MMC Charts', 'MMC', 'CHART', 'AI chart generation is not connected.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.mmc.charts.generate']),
            $this->capability('mmc.financial.analyze', 'mmc_intelligence', 'Analyze MMC Financial Data', 'MMC', 'ANALYZE', 'AI financial analysis is not connected to a validated data service.', [], [], 'unavailable', null, [], 'critical', 'none', 'Not available', ['ai.mmc.financial.analyze']),
            $this->capability('ai.records.view', 'ai_business_actions', 'View Records', '*', 'READ', 'Generic AI record browsing is not implemented; use authorized module-specific search.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.records.view']),
            $this->capability('ai.records.create', 'ai_business_actions', 'Create Records', '*', 'CREATE', 'Generic AI record creation is not implemented.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available', ['ai.records.create']),
            $this->capability('ai.records.edit', 'ai_business_actions', 'Edit Records', '*', 'UPDATE', 'Generic AI record editing is not implemented.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available', ['ai.records.edit']),
            $this->capability('ai.records.archive', 'ai_business_actions', 'Archive Records', '*', 'ARCHIVE', 'AI record archiving is not implemented.', [], [], 'unavailable', null, [], 'critical', 'explicit_confirmation', 'Not available', ['ai.records.archive']),
            $this->capability('ai.records.delete', 'ai_business_actions', 'Delete Records', '*', 'DELETE', 'AI record deletion is not implemented.', [], [], 'unavailable', null, [], 'critical', 'explicit_confirmation', 'Not available', ['ai.records.delete']),
            $this->capability('ai.documents.pdf', 'documents_analytics', 'Generate PDF', '*', 'DOCUMENT', 'AI-generated business PDFs are not connected.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available', ['ai.documents.pdf.generate']),
            $this->capability('ai.excel.export', 'documents_analytics', 'Export Excel', '*', 'EXPORT', 'AI workbook export is not connected.', [], [], 'unavailable', null, [], 'moderate', 'explicit_confirmation', 'Not available', ['ai.excel.export']),
            $this->capability('ai.excel.import', 'documents_analytics', 'Import Excel', '*', 'IMPORT', 'AI workbook import is not connected.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available', ['ai.excel.import']),
            $this->capability('ai.excel.analyze', 'documents_analytics', 'Analyze Excel', '*', 'ANALYZE', 'AI workbook analysis is not connected.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.excel.analyze']),
            $this->capability('ai.charts.generate', 'documents_analytics', 'Generate Charts', '*', 'CHART', 'AI chart generation is not connected.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.charts.generate']),
            $this->capability('ai.data.forecast', 'documents_analytics', 'Forecast Data', '*', 'FORECAST', 'Forecasting is not connected to a validated data service.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.data.forecast']),
            $this->capability('ai.documents.process', 'documents_analytics', 'Process Uploaded Documents', '*', 'DOCUMENT', 'Uploaded-document processing is not implemented.', [], [], 'unavailable', null, [], 'sensitive_read', 'none', 'Not available', ['ai.documents.process']),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function groupedFor(User $user, ?Company $company, bool $enabled, bool $configured): array
    {
        $categoryAliases = [
            'page_help' => 'core_ai',
            'view_search' => 'mi_intelligence',
            'create_records' => 'ai_business_actions',
            'edit_update' => 'ai_business_actions',
            'pdf_generation' => 'documents_analytics',
            'excel_tools' => 'documents_analytics',
            'charts_analytics' => 'documents_analytics',
            'forecasting' => 'documents_analytics',
            'document_intelligence' => 'documents_analytics',
            'workflow_assistant' => 'core_ai',
            'proactive_assistance' => 'core_ai',
        ];
        $capabilities = collect($this->definitions())->map(function (array $capability) use ($user, $company, $enabled, $configured, $categoryAliases): array {
            $capability['category'] = $categoryAliases[$capability['category']] ?? $capability['category'];
            $capability['permission_label'] = 'AI: '.implode(', ', $capability['ai_permissions'])
                .($capability['business_permissions'] === [] ? '' : ' · Business: '.implode(', ', $capability['business_permissions']));
            $diagnostic = $this->diagnostic($capability['id'], $user, $company, $enabled, $configured);
            $capability['status'] = $diagnostic['status'];
            $capability['diagnostic_reason'] = $diagnostic['reason'];

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
        return $this->authorizationDecision($id, $user, $company)['allowed'];
    }

    /** @return array{status: string, reason: string, allowed: bool} */
    public function diagnostic(string $id, User $user, ?Company $company, bool $enabled, bool $configured): array
    {
        $capability = collect($this->definitions())->firstWhere('id', $id);
        if ($capability === null || $capability['implementation'] === 'unavailable') {
            return ['status' => 'Not Implemented', 'reason' => 'No AI tool or service is connected. Assigning the permission will not enable it.', 'allowed' => false];
        }
        if (! $enabled || ! $configured) {
            return ['status' => 'Configuration Required', 'reason' => $enabled ? 'The AI provider is not configured.' : 'The AI Assistant is disabled globally.', 'allowed' => false];
        }
        if (! $user->can('ai.assistant.use')) {
            return ['status' => 'Permission Restricted', 'reason' => 'The user lacks ai.assistant.use.', 'allowed' => false];
        }
        if ($company === null || ! $this->hasActiveCompany($user, $company)
            || ($capability['company'] !== '*' && $capability['company'] !== $company->code)) {
            return ['status' => 'Company Restricted', 'reason' => $company === null ? 'Select an active company and verify the user belongs to it.' : 'The selected user does not have an active membership in the required company.', 'allowed' => false];
        }
        if ($capability['authorization_rule'] === 'mi_quotation'
            && (! Schema::hasTable('ai_quotation_workflows') || ! Schema::hasTable('ai_quotations'))) {
            return ['status' => 'Configuration Required', 'reason' => 'The quotation workflow database tables are not installed.', 'allowed' => false];
        }
        $decision = $this->authorizationDecision($id, $user, $company);
        if (! $decision['allowed']) {
            return [
                'status' => $decision['kind'] === 'permission' ? 'Permission Restricted' : 'Business Access Restricted',
                'reason' => $decision['reason'],
                'allowed' => false,
            ];
        }

        return ['status' => 'Available', 'reason' => 'All required AI permissions, business access checks, and company membership are satisfied.', 'allowed' => true];
    }

    /** @return array{allowed: bool, kind: string, reason: string} */
    private function authorizationDecision(string $id, User $user, Company $company): array
    {
        $capability = collect($this->definitions())->firstWhere('id', $id);
        if ($capability === null || $capability['implementation'] === 'unavailable') {
            return ['allowed' => false, 'kind' => 'permission', 'reason' => 'The requested capability is not implemented.'];
        }
        if (! $user->can('ai.assistant.use')) {
            return ['allowed' => false, 'kind' => 'permission', 'reason' => 'The user lacks ai.assistant.use.'];
        }
        if (! $this->hasActiveCompany($user, $company)) {
            return ['allowed' => false, 'kind' => 'company', 'reason' => 'The user lacks active membership in the selected company.'];
        }
        if ($capability['company'] !== '*' && $capability['company'] !== $company->code) {
            return ['allowed' => false, 'kind' => 'company', 'reason' => 'This capability belongs to a different company module.'];
        }
        if (! $this->hasBusinessPermissions($user, $capability['ai_permissions'])) {
            return ['allowed' => false, 'kind' => 'permission', 'reason' => 'One or more required AI capability permissions are missing: '.implode(', ', array_filter($capability['ai_permissions'], fn (string $permission): bool => ! $user->can($permission))).'.'];
        }
        if (($capability['authorization_rule'] !== 'mi_approvals' && ! $this->hasBusinessPermissions($user, $capability['business_permissions']))
            || ! $this->businessPolicyAllows($capability['authorization_rule'], $user, $company)) {
            return ['allowed' => false, 'kind' => 'business', 'reason' => 'The user does not satisfy the existing business-module permission or policy.'];
        }

        return ['allowed' => true, 'kind' => 'available', 'reason' => 'Authorized.'];
    }

    private function businessPolicyAllows(string $rule, User $user, Company $company): bool
    {
        return match ($rule) {
            'page_help' => $user->can('ai.page-help.use'),
            'mi_catalog' => $company->code === 'MI' && $user->can('mi.product-catalog.view'),
            'mi_prices' => $company->code === 'MI' && $user->can('mi.product-catalog.view') && $user->can('mi.product-prices.view'),
            'mi_quotation' => $company->code === 'MI' && $user->can('mi.quotation.create') && $user->can('mi.product-prices.view'),
            'mi_own_budget' => $company->code === 'MI' && $user->hasMIUserAccess(),
            'mi_liquidations' => $company->code === 'MI' && $user->can('mi.liquidation.view'),
            'mi_accounting' => $company->code === 'MI' && $user->hasAccountingRole() && $user->can('mi.accounting.dashboard.view'),
            'mi_approvals' => $company->code === 'MI' && ($user->canApproveMI('mi.budget.approve') || $user->canApproveMI('mi.travel.approve')),
            'mmc_projects' => $company->code === 'MMC' && Gate::forUser($user)->allows('viewAny', ProjectInformation::class),
            'mmc_delivery_analysis', 'mmc_delivery_search' => $company->code === 'MMC'
                && Gate::forUser($user)->allows('viewAny', ProjectInformation::class)
                && Gate::forUser($user)->allows('viewMmcDeliveryMonitoring'),
            'mmc_operations_snapshot' => $company->code === 'MMC'
                && Gate::forUser($user)->allows('viewAny', ProjectInformation::class)
                && Gate::forUser($user)->allows('viewMmcDeliveryMonitoring')
                && Gate::forUser($user)->allows('viewMmcInventory'),
            'mmc_inventory' => $company->code === 'MMC' && Gate::forUser($user)->allows('viewMmcInventory'),
            'ai_settings' => $user->can('ai.settings.manage'),
            'conversation_history' => $user->can('ai.assistant.use'),
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

    private function hasActiveCompany(User $user, Company $company): bool
    {
        return $user->can('ai.assistant.use')
            && (bool) $company->is_active
            && $user->currentCompany()?->getKey() === $company->getKey()
            && $user->belongsToCompany($company->getKey());
    }

    /** @param array<int, string> $businessPermissions
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $toolNames
     * @return array<string, mixed>
     */
    private function capability(string $id, string $category, string $label, string $company, string $operation, string $description, array $businessPermissions, array $roles, string $authorizationRule, ?string $handler, array $toolNames, string $risk, string $confirmation, string $audit, array $declaredAiPermissions = []): array
    {
        $implemented = $handler !== null;
        $partial = in_array($category, ['charts_analytics', 'workflow_assistant', 'proactive_assistance'], true);
        $aiPermissions = $declaredAiPermissions !== [] ? $declaredAiPermissions : match ($authorizationRule) {
            'page_help' => ['ai.page-help.use'],
            'mi_catalog' => ['ai.business-search.use', 'ai.mi.products.search', 'ai.mi.products.specifications'],
            'mi_prices' => ['ai.business-search.use', 'ai.mi.prices.lookup'],
            'mi_quotation' => ['ai.business-search.use', 'ai.mi.quotation.create'],
            'mi_own_budget', 'mi_liquidations' => ['ai.business-search.use', 'ai.mi.budget-liquidation.assist'],
            'mi_accounting', 'mi_approvals' => ['ai.business-search.use', 'ai.mi.accounting.reports'],
            'mmc_projects' => ['ai.business-search.use', 'ai.mmc.projects.view'],
            'mmc_delivery_analysis' => ['ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view', 'ai.mmc.deliveries.analyze'],
            'mmc_delivery_search' => ['ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view'],
            'mmc_operations_snapshot' => ['ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view', 'ai.mmc.deliveries.analyze', 'ai.mmc.inventory.view'],
            'mmc_inventory' => ['ai.business-search.use', 'ai.mmc.inventory.view'],
            default => ['ai.assistant.use'],
        };

        return [
            'id' => $id,
            'category' => $category,
            'label' => $label,
            'company' => $company,
            'operation' => $operation,
            'description' => $description,
            'business_permissions' => $businessPermissions,
            'ai_permissions' => $aiPermissions,
            'roles' => [],
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
                'search_mi_products' => ['type' => 'array', 'items' => ['id' => 'integer', 'name' => 'string', 'sku' => 'string|null', 'category' => 'string|null', 'sub_category' => 'string|null', 'collection' => 'string|null', 'materials' => 'array', 'colors' => 'array', 'specifications' => 'verified product details and dimensions', 'url' => 'authorized MI product page URL']],
                'lookup_mi_product_prices' => ['type' => 'array', 'items' => ['id' => 'integer', 'name' => 'string', 'sku' => 'string|null', 'specifications' => 'verified product details and dimensions', 'selling_price' => 'number|null', 'currency' => 'string', 'url' => 'authorized MI product page URL']],
                'mi_product_catalog_summary' => ['type' => 'object', 'properties' => ['product_count' => 'integer', 'products_missing_cop' => 'integer|null']],
                'mi_products_needing_cop' => ['type' => 'array', 'items' => ['id' => 'integer', 'name' => 'string', 'sku' => 'string|null']],
                'search_mmc_projects' => ['type' => 'object', 'properties' => ['status' => 'found|no_results', 'projects' => 'array of authorized matching projects']],
                'search_mmc_deliveries' => ['type' => 'array', 'items' => ['delivery' => 'authorized MMC delivery record']],
                'search_mmc_inventory' => ['type' => 'array', 'items' => ['inventory' => 'authorized MMC inventory record']],
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
