<?php

namespace App\Services;

use App\Http\Controllers\Admin\AiConfigurationController;
use App\Http\Controllers\AiLiquidationAttachmentController;
use App\Http\Controllers\AssistantController;
use App\Models\Company;
use App\Models\MI_Liquidation;
use App\Models\User;
use App\Policies\ProjectInformationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
            $this->capability('mi.approvals.pending_summary', 'mi_intelligence', 'MI Pending Approval Summary', 'MI', 'READ', 'Summarize only budget and travel approval queues the user may review.', [], [], 'mi_approvals', 'AiReadOnlyToolGateway', ['mi_pending_approval_summary'], 'sensitive_read', 'none', 'AI activity log'),
            $this->capability('mi.quotation.draft', 'create_records', 'MI Quotation Draft', 'MI', 'CREATE', 'Prepare and confirm an idempotent quotation draft using current authorized selling prices.', ['mi.quotation.create', 'mi.product-prices.view'], [], 'mi_quotation', 'AiQuotationWorkflowService', [], 'high', 'explicit_confirmation', 'Quotation workflow audit'),
            $this->capability('business.records.create', 'create_records', 'Create other business records', '*', 'CREATE', 'General conversational creation is not connected to existing application form services.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.records.update', 'edit_update', 'Edit existing business records', '*', 'UPDATE', 'No generic AI edit tool is implemented; edits must use each module’s existing form and validation.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.records.delete', 'edit_update', 'Delete or archive records', '*', 'DELETE / ARCHIVE', 'The assistant cannot delete or archive business records.', [], [], 'unavailable', null, [], 'critical', 'explicit_confirmation', 'Not available'),
            $this->capability('business.documents.pdf', 'pdf_generation', 'Generate business PDFs', '*', 'PDF GENERATION', 'AI-controlled PDF generation across products, quotations, finance and operations is not connected.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.workbooks', 'excel_tools', 'Import, export and analyze workbooks', '*', 'EXCEL IMPORT / EXPORT', 'Application workbook flows are separate; no AI workbook analysis or confirmed import/export tool is connected.', [], [], 'unavailable', null, [], 'high', 'explicit_confirmation', 'Not available'),
            $this->capability('business.charts', 'charts_analytics', 'Render charts and reports', '*', 'CHARTS / REPORTING', 'Some read tools return verified counts, but chart rendering and AI report generation are not implemented.', [], [], 'unavailable', 'AiReadOnlyToolGateway (verified metrics only)', [], 'moderate', 'none', 'Existing read-tool activity'),
            $this->capability('business.forecasts', 'forecasting', 'Statistical business forecasting', '*', 'FORECASTING / ANALYSIS', 'No deterministic forecasting service is connected to the assistant.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available'),
            $this->capability('business.document_intelligence', 'document_intelligence', 'Uploaded document intelligence', '*', 'DOCUMENT PROCESSING', 'The assistant does not ingest uploaded files or read their contents.', [], [], 'unavailable', null, [], 'sensitive_read', 'none', 'Not available'),
            $this->capability('business.workflow_guidance', 'workflow_assistant', 'Workflow guidance', '*', 'READ', 'Explain page context and safe next steps. Business workflow execution continues through existing authorized forms and actions.', [], [], 'workflow_guidance', 'AiModuleRegistry (page guidance only)', [], 'moderate', 'none', 'AI activity log'),
            $this->capability('business.proactive_suggestions', 'proactive_assistance', 'Context-aware next steps', '*', 'READ', 'Offer the existing MI quotation next step only when the user can access its underlying product and quotation actions.', [], [], 'proactive_suggestions', 'AiAssistantService (quotation suggestion only)', [], 'low', 'none', 'AI activity log'),
            $this->capability('ai.configuration', 'core_ai', 'AI Configuration', '*', 'ADMIN', 'Manage system-wide AI provider settings.', [], [], 'ai_settings', 'AiConfigurationController', [], 'high', 'explicit_confirmation', 'Administrator audit log', ['ai.settings.manage']),
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
            $this->capability('ai.attachments.upload', 'documents_analytics', 'Upload assistant attachments', '*', 'UPLOAD', 'Upload allowlisted private attachments to an owned assistant conversation.', [], [], 'attachment_upload', 'AiLiquidationAttachmentController::upload', [], 'sensitive_read', 'explicit_confirmation', 'AI attachment audit', ['ai.attachments.upload']),
            $this->capability('ai.attachments.read', 'documents_analytics', 'Read assistant attachments', '*', 'READ', 'Read only the current user’s unexpired attachments from the active company.', [], [], 'attachment_read', 'AssistantController::stream', [], 'sensitive_read', 'explicit_confirmation', 'AI attachment audit', ['ai.attachments.read']),
            $this->capability('ai.excel.analyze', 'documents_analytics', 'Analyze an Excel workbook', 'MI', 'ANALYZE', 'Inspect a private workbook and extract rows for the existing MI liquidation form.', [], [], 'mi_liquidation_draft', 'AiLiquidationExcelDraftService', [], 'sensitive_read', 'explicit_confirmation', 'AI attachment audit', ['ai.attachments.upload', 'ai.attachments.read', 'ai.excel.analyze', 'ai.mi.liquidation.draft', 'ai.mi.budget-liquidation.assist']),
            $this->capability('ai.mi.liquidation.form.fill', 'documents_analytics', 'Fill MI liquidation form', 'MI', 'FORM PREFILL', 'Prefill the existing ordinary MI liquidation form with a reviewed workbook draft; saving remains a separate employee action.', [], [], 'mi_liquidation_form_fill', 'AiLiquidationAttachmentController::fill', [], 'sensitive_read', 'explicit_confirmation', 'AI attachment audit', ['ai.attachments.read', 'ai.excel.analyze', 'ai.mi.liquidation.draft', 'ai.mi.liquidation.form.fill', 'ai.mi.budget-liquidation.assist']),
            $this->capability('ai.charts.generate', 'documents_analytics', 'Generate Charts', '*', 'CHART', 'AI chart generation is not connected.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.charts.generate']),
            $this->capability('ai.data.forecast', 'documents_analytics', 'Forecast Data', '*', 'FORECAST', 'Forecasting is not connected to a validated data service.', [], [], 'unavailable', null, [], 'moderate', 'none', 'Not available', ['ai.data.forecast']),
            $this->capability('ai.documents.process', 'documents_analytics', 'Process uploaded documents', '*', 'ANALYZE', 'Read authorized PDF, DOCX, and TXT attachments in the current AI request.', [], [], 'document_processing', 'AiLiquidationExcelDraftService::inspectAttachment', [], 'sensitive_read', 'explicit_confirmation', 'AI attachment audit', ['ai.documents.process', 'ai.attachments.read']),
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
            $capability['diagnostic_details'] = $diagnostic;
            $capability['implementation_status'] = $diagnostic['implementation_status'];
            $capability['company_scope'] = $diagnostic['company_scope'];
            $capability['required_ai_permissions'] = $diagnostic['required_ai_permissions'];
            $capability['granted_ai_permissions'] = $diagnostic['granted_ai_permissions'];
            $capability['missing_ai_permissions'] = $diagnostic['missing_ai_permissions'];
            $capability['business_policy_requirements'] = $diagnostic['business_policy_requirements'];
            $capability['failed_business_checks'] = $diagnostic['failed_business_checks'];

            return $capability;
        })->groupBy('category');

        return collect($this->categories())->map(function (array $category) use ($capabilities): array {
            $items = $capabilities->get($category['id'], collect())->values();
            $category['capabilities'] = $items->all();
            $category['implemented_count'] = $items->whereIn('implementation', ['implemented', 'partial'])->count();
            $category['available_count'] = $items->whereIn('status', ['Implemented', 'Partially Implemented'])->count();

            return $category;
        })->all();
    }

    public function allows(string $id, User $user, ?Company $company): bool
    {
        return $this->authorizationDecision($id, $user, $company, true)['allowed'];
    }

    /** @return array{status: string, reason: string, allowed: bool, implementation_status: string, company_scope: string, required_ai_permissions: array<int, string>, granted_ai_permissions: array<int, string>, missing_ai_permissions: array<int, string>, business_policy_requirements: array<int, string>, failed_business_checks: array<int, string>} */
    public function diagnostic(string $id, User $user, ?Company $company, bool $enabled, bool $configured): array
    {
        $capability = collect($this->definitions())->firstWhere('id', $id);
        if ($capability === null) {
            return [...$this->formatDiagnostic(null, 'Not Implemented', 'This capability is not registered.', [], [], []), 'checks' => []];
        }

        $decision = $this->authorizationDecision($id, $user, $company, false);
        $withChecks = function (array $diagnostic) use ($decision, $id, $enabled, $configured): array {
            $checks = $decision['checks'];
            if ($id !== 'ai.configuration') {
                $checks[] = ['type' => 'system_setting', 'name' => 'ai_assistant.enabled', 'granted' => $enabled];
                $checks[] = ['type' => 'configuration', 'name' => 'openai.credential_available', 'granted' => $configured];
            }

            return [...$diagnostic, 'checks' => $checks];
        };
        $implementationStatus = $this->implementationStatus($capability);
        if ($implementationStatus === 'Not Implemented') {
            return $withChecks($this->formatDiagnostic($capability, $implementationStatus, 'No executable service action or registered route is available for this capability.', $decision['missing_ai_permissions'], $decision['failed_business_checks'], $decision['required_ai_permissions'], $decision['granted_ai_permissions']));
        }

        if (! $decision['allowed']) {
            return $withChecks($this->formatDiagnostic($capability, $decision['status'], $decision['reason'], $decision['missing_ai_permissions'], $decision['failed_business_checks'], $decision['required_ai_permissions'], $decision['granted_ai_permissions']));
        }

        if ($id !== 'ai.configuration' && ! $enabled) {
            return $withChecks($this->formatDiagnostic($capability, 'Disabled', 'The AI Assistant is disabled globally.', [], [], $decision['required_ai_permissions'], $decision['granted_ai_permissions']));
        }
        if ($id !== 'ai.configuration' && ! $configured) {
            return $withChecks($this->formatDiagnostic($capability, 'Configuration Required', 'No OpenAI credential is available.', [], [], $decision['required_ai_permissions'], $decision['granted_ai_permissions']));
        }
        if (in_array($id, ['mi.quotation.draft', 'business.proactive_suggestions'], true)
            && (! Schema::hasTable('ai_quotation_workflows') || ! Schema::hasTable('ai_quotations'))) {
            return $withChecks($this->formatDiagnostic($capability, 'Configuration Required', 'The quotation workflow database tables are not installed.', [], [], $decision['required_ai_permissions'], $decision['granted_ai_permissions']));
        }

        return $withChecks($this->formatDiagnostic($capability, $implementationStatus, 'All required AI permissions, company membership, and business-policy checks passed.', [], [], $decision['required_ai_permissions'], $decision['granted_ai_permissions'], true));
    }

    /** @return array{allowed: bool, status: string, reason: string, required_ai_permissions: array<int, string>, granted_ai_permissions: array<int, string>, missing_ai_permissions: array<int, string>, failed_business_checks: array<int, string>, checks: array<int, array{type: string, name: string, granted: bool}>} */
    private function authorizationDecision(string $id, User $user, ?Company $company, bool $requireCurrentCompany): array
    {
        $capability = collect($this->definitions())->firstWhere('id', $id);
        $requiredAiPermissions = $id === 'ai.configuration'
            ? ['ai.settings.manage']
            : array_values(array_unique(['ai.assistant.use', ...($capability['ai_permissions'] ?? [])]));
        $grantedAiPermissions = array_values(array_filter($requiredAiPermissions, fn (string $permission): bool => $user->can($permission)));
        $missingAiPermissions = array_values(array_diff($requiredAiPermissions, $grantedAiPermissions));
        $isGlobal = $capability !== null && $id === 'ai.configuration';
        $membershipGranted = $isGlobal || ($company !== null
            && (bool) $company->is_active
            && $user->belongsToCompany($company->getKey())
            && (! $requireCurrentCompany || $user->currentCompany()?->getKey() === $company->getKey()));
        $companyScopeGranted = $isGlobal || ($company !== null && ($capability['company'] === '*' || $capability['company'] === $company->code));
        $businessChecks = $capability !== null && $company !== null && ! $isGlobal ? $this->businessChecks($capability, $user, $company) : [];
        $failedBusinessChecks = array_map(fn (string $name): string => $name.' check failed', array_keys(array_filter($businessChecks, fn (bool $passed): bool => ! $passed)));
        $checks = array_map(fn (string $permission): array => ['type' => 'ai_permission', 'name' => $permission, 'granted' => $user->can($permission)], $requiredAiPermissions);
        $checks[] = ['type' => 'company_membership', 'name' => $isGlobal ? 'global scope; membership not required' : 'active membership in selected company', 'granted' => $membershipGranted];
        $checks[] = ['type' => 'company_scope', 'name' => $isGlobal ? 'global' : ($capability['company'] ?? 'unknown'), 'granted' => $companyScopeGranted];
        foreach ($businessChecks as $name => $passed) {
            $checks[] = ['type' => 'business_policy', 'name' => $name, 'granted' => $passed];
        }

        $implemented = $capability !== null && $this->implementationStatus($capability) !== 'Not Implemented';
        $checks[] = ['type' => 'implementation', 'name' => $capability['handler'] ?? 'executable handler', 'granted' => $implemented];

        if (! $implemented) {
            $status = 'Not Implemented';
            $reason = 'The requested capability has no executable implementation.';
        } elseif ($missingAiPermissions !== []) {
            $status = 'Permission Restricted';
            $reason = 'Missing AI permission(s): '.implode(', ', $missingAiPermissions).'.';
        } elseif (! $membershipGranted || ! $companyScopeGranted) {
            $status = 'Company Restricted';
            $reason = ! $membershipGranted
                ? 'Company membership check failed: the company must be active, assigned to the inspected user, and selected for runtime access.'
                : "Company scope check failed: this capability requires {$capability['company']} and {$company?->code} is selected.";
        } elseif ($failedBusinessChecks !== []) {
            $status = 'Business Access Restricted';
            $reason = 'Failed business access check(s): '.implode('; ', $failedBusinessChecks);
        } else {
            $status = $isGlobal ? 'Implemented' : $this->implementationStatus($capability);
            $reason = $isGlobal ? 'ai.settings.manage is granted. System-wide configuration does not require company membership.' : 'Authorized.';
        }

        return ['allowed' => $status === 'Implemented' || $status === 'Partially Implemented', 'status' => $status, 'reason' => $reason, 'required_ai_permissions' => $requiredAiPermissions, 'granted_ai_permissions' => $grantedAiPermissions, 'missing_ai_permissions' => $missingAiPermissions, 'failed_business_checks' => $failedBusinessChecks, 'checks' => $checks];
    }

    /** @return array<int, string> */
    private function failedBusinessChecks(array $capability, User $user, Company $company): array
    {
        return array_map(fn (string $name): string => $name.' check failed', array_keys(array_filter($this->businessChecks($capability, $user, $company), fn (bool $passed): bool => ! $passed)));
    }

    /** @return array<string, bool> */
    private function businessChecks(array $capability, User $user, Company $company): array
    {
        $checks = [];
        foreach ($capability['business_permissions'] as $permission) {
            if ($capability['authorization_rule'] === 'mi_approvals') {
                break;
            }
            $passed = str_contains($permission, ' or ')
                ? $user->hasAnyPermission(explode(' or ', $permission))
                : $user->can($permission);
            $checks["Business permission {$permission}"] = $passed;
        }

        $ruleChecks = match ($capability['authorization_rule']) {
            'page_help', 'workflow_guidance' => [
                'ai.page-help.use' => $user->can('ai.page-help.use'),
                'AiModuleRegistry::pageContext action' => class_exists(AiModuleRegistry::class) && method_exists(AiModuleRegistry::class, 'pageContext'),
            ],
            'mi_catalog' => ['mi.product-catalog.view' => $company->code === 'MI' && $user->can('mi.product-catalog.view')],
            'mi_prices' => ['mi.product-prices.view' => $company->code === 'MI' && $user->can('mi.product-prices.view')],
            'mi_quotation', 'proactive_suggestions' => [
                'MI company scope' => $company->code === 'MI',
                'mi.quotation.create' => $user->can('mi.quotation.create'),
                'mi.product-prices.view' => $user->can('mi.product-prices.view'),
                'AiQuotationWorkflowService start/preview/confirm actions' => class_exists(AiQuotationWorkflowService::class)
                    && method_exists(AiQuotationWorkflowService::class, 'start')
                    && method_exists(AiQuotationWorkflowService::class, 'preview')
                    && method_exists(AiQuotationWorkflowService::class, 'confirm'),
            ],
            'mi_own_budget' => ['MI employee role' => $company->code === 'MI' && $user->hasAnyRole(['user', 'Executive', 'executive'])],
            'mi_liquidations' => ['mi.liquidation.view' => $company->code === 'MI' && $user->can('mi.liquidation.view')],
            'mi_accounting' => ['MI accounting role and mi.accounting.dashboard.view' => $company->code === 'MI' && $user->hasAccountingRole() && $user->can('mi.accounting.dashboard.view')],
            'mi_approvals' => ['MI approval role or approval permission' => $company->code === 'MI'
                && ($user->hasAnyRole(['Executive', 'executive', 'Administrator']) || $user->can('mi.budget.approve') || $user->can('mi.travel.approve'))],
            'mmc_projects' => ['ProjectInformation viewAny policy' => $company->code === 'MMC' && app(ProjectInformationPolicy::class)->viewAnyForCompany($user, $company)],
            'mmc_delivery_analysis', 'mmc_delivery_search' => [
                'ProjectInformation viewAny policy' => $company->code === 'MMC' && app(ProjectInformationPolicy::class)->viewAnyForCompany($user, $company),
                'viewMmcDeliveryMonitoring Gate' => Gate::forUser($user)->allows('viewMmcDeliveryMonitoring', $company),
            ],
            'mmc_operations_snapshot' => [
                'ProjectInformation viewAny policy' => $company->code === 'MMC' && app(ProjectInformationPolicy::class)->viewAnyForCompany($user, $company),
                'viewMmcDeliveryMonitoring Gate' => Gate::forUser($user)->allows('viewMmcDeliveryMonitoring', $company),
                'viewMmcInventory Gate' => Gate::forUser($user)->allows('viewMmcInventory', $company),
            ],
            'mmc_inventory' => ['viewMmcInventory Gate' => Gate::forUser($user)->allows('viewMmcInventory', $company)],
            'mi_liquidation_draft' => [
                'MI company scope' => $company->code === 'MI',
                'MI_Liquidation create policy' => $company->code === 'MI' && Gate::forUser($user)->allows('create', MI_Liquidation::class),
                'Excel parser and draft-form actions' => class_exists(AiLiquidationExcelDraftService::class)
                    && method_exists(AiLiquidationExcelDraftService::class, 'inspect')
                    && Route::has('assistant.liquidation.attach') && Route::has('assistant.liquidation.fill'),
            ],
            'mi_liquidation_form_fill' => [
                'MI company scope' => $company->code === 'MI',
                'MI_Liquidation create policy' => $company->code === 'MI' && Gate::forUser($user)->allows('create', MI_Liquidation::class),
                'Form-fill action' => Route::has('assistant.liquidation.fill'),
            ],
            'attachment_upload' => [
                'Attachment upload action' => class_exists(AiLiquidationAttachmentController::class)
                    && method_exists(AiLiquidationAttachmentController::class, 'upload')
                    && Route::has('assistant.liquidation.attach'),
                'Attachment read capability' => $user->can('ai.attachments.read'),
            ],
            'attachment_read' => [
                'Attachment read action' => class_exists(AssistantController::class)
                    && method_exists(AssistantController::class, 'stream')
                    && Route::has('assistant.messages.stream'),
                'Attachment upload capability' => $user->can('ai.attachments.upload'),
                'Document or Excel analysis permission' => $user->can('ai.documents.process') || $user->can('ai.excel.analyze'),
            ],
            'document_processing' => [
                'Document parsers and assistant request integration' => class_exists(AiLiquidationExcelDraftService::class)
                    && method_exists(AiLiquidationExcelDraftService::class, 'inspectAttachment')
                    && class_exists(AiAssistantService::class) && method_exists(AiAssistantService::class, 'respond'),
            ],
            'ai_settings' => ['ai.settings.manage' => $user->can('ai.settings.manage')],
            'conversation_history' => ['AssistantController conversation routes' => Route::has('assistant.conversations.index') && Route::has('assistant.conversations.store') && Route::has('assistant.conversations.show') && Route::has('assistant.conversations.destroy')],
            default => [],
        };

        return array_merge($checks, $ruleChecks);
    }

    public function allowsTool(string $toolName, User $user, Company $company): bool
    {
        $capability = collect($this->definitions())->first(fn (array $item): bool => in_array($toolName, $item['tool_names'], true));

        return $capability !== null && $this->allows($capability['id'], $user, $company);
    }

    private function hasActiveCompany(User $user, Company $company, bool $requireCurrentCompany): bool
    {
        return $user->can('ai.assistant.use')
            && (bool) $company->is_active
            && (! $requireCurrentCompany || $user->currentCompany()?->getKey() === $company->getKey())
            && $user->belongsToCompany($company->getKey());
    }

    /** @param array<string, mixed>|null $capability
     * @param  array<int, string>  $missingAiPermissions
     * @param  array<int, string>  $failedBusinessChecks
     * @param  array<int, string>  $requiredAiPermissions
     * @param  array<int, string>  $grantedAiPermissions
     * @return array{status: string, reason: string, allowed: bool, implementation_status: string, company_scope: string, required_ai_permissions: array<int, string>, granted_ai_permissions: array<int, string>, missing_ai_permissions: array<int, string>, business_policy_requirements: array<int, string>, failed_business_checks: array<int, string>}
     */
    private function formatDiagnostic(?array $capability, string $status, string $reason, array $missingAiPermissions, array $failedBusinessChecks, array $requiredAiPermissions, array $grantedAiPermissions = [], bool $allowed = false): array
    {
        $ruleRequirements = match ($capability['authorization_rule'] ?? null) {
            'page_help' => ['ai.page-help.use', 'AiModuleRegistry pageContext action'],
            'mi_catalog' => ['MI company scope', 'mi.product-catalog.view'],
            'mi_prices' => ['MI company scope', 'mi.product-catalog.view', 'mi.product-prices.view'],
            'mi_quotation' => ['MI company scope', 'mi.quotation.create', 'mi.product-prices.view', 'quotation workflow actions'],
            'proactive_suggestions' => ['MI quotation permissions and executable quote workflow', 'MI company scope', 'mi.quotation.create', 'mi.product-prices.view'],
            'mi_own_budget' => ['MI company scope', 'MI employee role'],
            'mi_liquidations' => ['MI company scope', 'mi.liquidation.view'],
            'mi_accounting' => ['MI company scope', 'accounting role', 'mi.accounting.dashboard.view'],
            'mi_approvals' => ['MI company scope', 'Executive/Administrator role or an approval permission'],
            'mmc_delivery_analysis', 'mmc_delivery_search' => ['ProjectInformation viewAny policy', 'viewMmcDeliveryMonitoring Gate'],
            'mmc_operations_snapshot' => ['ProjectInformation viewAny policy', 'viewMmcDeliveryMonitoring Gate', 'viewMmcInventory Gate'],
            'mmc_inventory' => ['viewMmcInventory Gate'],
            'mi_liquidation_draft' => ['MI company scope', 'MI_Liquidation create policy', 'Excel parser and draft-form actions'],
            'mi_liquidation_form_fill' => ['MI company scope', 'MI_Liquidation create policy', 'explicit form-fill permission'],
            'attachment_upload' => ['active company membership', 'owned assistant conversation', 'allowlisted private upload action'],
            'attachment_read' => ['active company membership', 'attachment owner and company scope', 'authorized AI request action'],
            'document_processing' => ['attachment read permission', 'allowlisted PDF, DOCX, and TXT text extraction or PDF input action'],
            'mmc_projects' => ['ProjectInformation viewAny policy'],
            'ai_settings' => ['Global scope; ai.settings.manage'],
            'workflow_guidance' => ['AiModuleRegistry pageContext action; no workflow execution authority'],
            default => [],
        };
        $businessRequirements = array_values(array_unique([
            ...($capability['business_permissions'] ?? []),
            ...$ruleRequirements,
        ]));

        return [
            'status' => $status,
            'reason' => $reason,
            'allowed' => $allowed,
            'implementation_status' => $capability['implementation_status'] ?? $status,
            'company_scope' => $capability['company'] ?? '*',
            'required_ai_permissions' => $requiredAiPermissions,
            'granted_ai_permissions' => $grantedAiPermissions,
            'missing_ai_permissions' => $missingAiPermissions,
            'business_policy_requirements' => $businessRequirements,
            'failed_business_checks' => $failedBusinessChecks,
        ];
    }

    /** @param array<string, mixed> $capability */
    private function implementationStatus(array $capability): string
    {
        if (($capability['authorization_rule'] ?? 'unavailable') === 'unavailable' || ! $this->registeredActionsAreUsable($capability)) {
            return 'Not Implemented';
        }

        return in_array($capability['authorization_rule'], ['workflow_guidance', 'proactive_suggestions'], true)
            ? 'Partially Implemented'
            : 'Implemented';
    }

    /** @param array<string, mixed> $capability */
    private function registeredActionsAreUsable(array $capability): bool
    {
        return match ($capability['authorization_rule']) {
            'page_help', 'workflow_guidance' => class_exists(AiModuleRegistry::class) && method_exists(AiModuleRegistry::class, 'pageContext'),
            'mi_catalog', 'mi_prices', 'mi_own_budget', 'mi_liquidations', 'mi_accounting', 'mi_approvals', 'mmc_projects', 'mmc_delivery_analysis', 'mmc_delivery_search', 'mmc_operations_snapshot', 'mmc_inventory' => $this->readToolActionsAreUsable($capability),
            'mi_quotation' => class_exists(AiQuotationWorkflowService::class)
                && method_exists(AiQuotationWorkflowService::class, 'start')
                && method_exists(AiQuotationWorkflowService::class, 'preview')
                && method_exists(AiQuotationWorkflowService::class, 'confirm'),
            'mi_liquidation_draft' => class_exists(AiLiquidationExcelDraftService::class)
                && method_exists(AiLiquidationExcelDraftService::class, 'inspect')
                && Route::has('assistant.liquidation.attach') && Route::has('assistant.liquidation.fill'),
            'mi_liquidation_form_fill' => class_exists(AiLiquidationAttachmentController::class)
                && method_exists(AiLiquidationAttachmentController::class, 'fill')
                && Route::has('assistant.liquidation.fill'),
            'attachment_upload' => class_exists(AiLiquidationAttachmentController::class)
                && method_exists(AiLiquidationAttachmentController::class, 'upload')
                && Route::has('assistant.liquidation.attach'),
            'attachment_read' => class_exists(AssistantController::class)
                && method_exists(AssistantController::class, 'stream') && Route::has('assistant.messages.stream'),
            'document_processing' => class_exists(AiLiquidationExcelDraftService::class)
                && method_exists(AiLiquidationExcelDraftService::class, 'inspectAttachment')
                && class_exists(AiAssistantService::class) && method_exists(AiAssistantService::class, 'respond'),
            'proactive_suggestions' => class_exists(AiAssistantService::class)
                && method_exists(AiAssistantService::class, 'respond')
                && class_exists(AiQuotationWorkflowService::class)
                && method_exists(AiQuotationWorkflowService::class, 'start')
                && method_exists(AiQuotationWorkflowService::class, 'preview')
                && method_exists(AiQuotationWorkflowService::class, 'confirm'),
            'ai_settings' => class_exists(AiConfigurationController::class)
                && method_exists(AiConfigurationController::class, 'index')
                && Route::has('admin.ai-configuration.index'),
            'conversation_history' => class_exists(AssistantController::class)
                && method_exists(AssistantController::class, 'conversations')
                && Route::has('assistant.conversations.index')
                && Route::has('assistant.conversations.store')
                && Route::has('assistant.conversations.show')
                && Route::has('assistant.conversations.destroy'),
            default => false,
        };
    }

    /** @param array<string, mixed> $capability */
    private function readToolActionsAreUsable(array $capability): bool
    {
        if (! class_exists(AiReadOnlyToolGateway::class) || ! method_exists(AiReadOnlyToolGateway::class, 'call')
            || ! collect($capability['tool_names'])->every(fn (string $toolName): bool => AiReadOnlyToolGateway::supportsTool($toolName))) {
            return false;
        }

        return match ($capability['authorization_rule']) {
            'mmc_projects' => class_exists(JarvisReadService::class) && method_exists(JarvisReadService::class, 'projectRecords') && Route::has('projects.dashboard'),
            'mmc_delivery_analysis' => class_exists(MmcProjectIntelligence::class) && method_exists(MmcProjectIntelligence::class, 'deliverySummary') && Route::has('deliveries.monitoring'),
            'mmc_delivery_search' => class_exists(JarvisReadService::class) && method_exists(JarvisReadService::class, 'deliveryRecords') && Route::has('deliveries.monitoring'),
            'mmc_operations_snapshot' => class_exists(JarvisReadService::class) && method_exists(JarvisReadService::class, 'dashboard') && class_exists(MmcProjectIntelligence::class) && method_exists(MmcProjectIntelligence::class, 'deliverySummary'),
            'mmc_inventory' => class_exists(JarvisReadService::class) && method_exists(JarvisReadService::class, 'inventoryRecords') && Route::has('warehouse.inventory.index'),
            default => true,
        };
    }

    /** @param array<int, string> $businessPermissions
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $toolNames
     * @return array<string, mixed>
     */
    private function capability(string $id, string $category, string $label, string $company, string $operation, string $description, array $businessPermissions, array $roles, string $authorizationRule, ?string $handler, array $toolNames, string $risk, string $confirmation, string $audit, array $declaredAiPermissions = []): array
    {
        $implemented = $handler !== null && $authorizationRule !== 'unavailable';
        $aiPermissions = $declaredAiPermissions !== [] ? $declaredAiPermissions : match ($authorizationRule) {
            'page_help' => ['ai.page-help.use'],
            'mi_catalog' => ['ai.business-search.use', 'ai.mi.products.search', 'ai.mi.products.specifications'],
            'mi_prices' => ['ai.business-search.use', 'ai.mi.prices.lookup'],
            'mi_quotation' => ['ai.business-search.use', 'ai.mi.quotation.create'],
            'mi_own_budget', 'mi_liquidations' => ['ai.business-search.use', 'ai.mi.budget-liquidation.assist'],
            'mi_liquidation_draft' => ['ai.excel.analyze', 'ai.mi.budget-liquidation.assist'],
            'mi_accounting', 'mi_approvals' => ['ai.business-search.use', 'ai.mi.accounting.reports'],
            'workflow_guidance' => ['ai.page-help.use'],
            'proactive_suggestions' => ['ai.business-search.use', 'ai.mi.quotation.create'],
            'mmc_projects' => ['ai.business-search.use', 'ai.mmc.projects.view'],
            'mmc_delivery_analysis' => ['ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view', 'ai.mmc.deliveries.analyze'],
            'mmc_delivery_search' => ['ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view'],
            'mmc_operations_snapshot' => ['ai.business-search.use', 'ai.mmc.projects.view', 'ai.mmc.deliveries.view', 'ai.mmc.deliveries.analyze', 'ai.mmc.inventory.view'],
            'mmc_inventory' => ['ai.business-search.use', 'ai.mmc.inventory.view'],
            default => ['ai.assistant.use'],
        };

        $capability = [
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
            'implementation' => 'unavailable',
        ];
        $implementationStatus = $this->implementationStatus($capability);
        $capability['implementation_status'] = $implementationStatus;
        $capability['implementation'] = match ($implementationStatus) {
            'Implemented' => 'implemented',
            'Partially Implemented' => 'partial',
            default => 'unavailable',
        };

        return $capability;
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
