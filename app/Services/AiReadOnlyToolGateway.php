<?php

namespace App\Services;

use App\Models\BudgetRequest;
use App\Models\Company;
use App\Models\MI_Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiReadOnlyToolGateway
{
    public function __construct(
        private AiMiProductPresenter $productPresenter,
        private JarvisReadService $jarvisReadService,
        private AccountingWorkspace $accountingWorkspace,
        private MIApprovalQueue $approvalQueue,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function tools(User $user, Company $company, array $context): array
    {
        $tools = [];

        if (! $this->isActiveCompany($user, $company)) {
            return $tools;
        }

        if ($this->canReadMiCatalog($user, $company)) {
            $tools[] = $this->productSearchTool('search_mi_products', 'Search MI product names, SKU or item code, category, collection, materials, and colors. Does not return selling prices.');
            $tools[] = ['type' => 'function', 'name' => 'mi_product_catalog_summary', 'description' => 'Return the MI product count and, when available, the count of products missing country of production (COP).', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
            $tools[] = ['type' => 'function', 'name' => 'mi_products_needing_cop', 'description' => 'List up to 25 MI products whose country of production (COP) is missing.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
        }

        if ($this->canViewMiPrices($user, $company)) {
            $tools[] = $this->productSearchTool('lookup_mi_product_prices', 'Search MI products and return their authorized selling price, if one is set. Use the MI product selling price field only; never use factory or purchase costs as selling prices.');
        }

        if ($company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            $tools[] = $this->searchTool('search_mmc_projects', 'Search MMC project records visible in the active MMC company.', 'Project name, code, or procuring entity');
            $tools[] = ['type' => 'function', 'name' => 'mmc_operations_snapshot', 'description' => 'Get calculated MMC project, delivery, package, and inventory totals for the active company workspace.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
            $tools[] = $this->searchTool('search_mmc_deliveries', 'Search read-only MMC delivery records, receipt statuses, and package counts.', 'Project, receipt number, or school to search');
        }

        if ($company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator', 'Warehouse_officer'])) {
            $tools[] = $this->searchTool('search_mmc_inventory', 'Search read-only MMC warehouse inventory by item, warehouse, or project.', 'Item, warehouse, or project to search');
        }

        if ($company->code === 'MI' && $user->hasMIUserAccess()) {
            $tools[] = ['type' => 'function', 'name' => 'my_mi_budget_requests', 'description' => 'List only the authenticated MI employee’s own budget requests and their status.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
        }

        if ($company->code === 'MI' && $user->hasAccountingRole() && $user->can('mi.accounting.dashboard.view')) {
            $tools[] = ['type' => 'function', 'name' => 'mi_accounting_queue_summary', 'description' => 'Return counts for MI accounting queues. Each count is filtered by the employee’s accounting permissions.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
        }

        if ($company->code === 'MI' && ($user->canApproveMI('mi.budget.approve') || $user->canApproveMI('mi.travel.approve'))) {
            $tools[] = ['type' => 'function', 'name' => 'mi_pending_approval_summary', 'description' => 'Return counts of pending MI budget and travel liquidation approvals accessible to this approver.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
        }

        Log::info('AI assistant tools available', [
            'user_id' => $user->getKey(),
            'company_id' => $company->getKey(),
            'tool_names' => array_column($tools, 'name'),
        ]);

        return $tools;
    }

    /** @param array<string, mixed> $arguments
     * @return array<int, array<string, mixed>>|array<string, mixed>
     */
    public function call(string $name, array $arguments, User $user, Company $company): array
    {
        Log::info('AI assistant tool selected', [
            'tool' => $name,
            'user_id' => $user->getKey(),
            'company_id' => $company->getKey(),
        ]);

        if (! $this->isActiveCompany($user, $company)) {
            Log::warning('AI assistant tool denied', ['tool' => $name, 'user_id' => $user->getKey(), 'company_id' => $company->getKey()]);
            abort(403, 'This assistant data tool is not available for your account.');
        }

        if ($name === 'mi_product_catalog_summary' && $this->canReadMiCatalog($user, $company)) {
            $summary = ['product_count' => MI_Product::query()->count()];
            if (Schema::hasColumn('mi_products', 'country_of_production')) {
                $summary['products_missing_cop'] = MI_Product::query()->where(function ($query): void {
                    $query->whereNull('country_of_production')->orWhereRaw("TRIM(country_of_production) = ''");
                })->count();
            } else {
                $summary['products_missing_cop'] = null;
                $summary['cop_tracking_available'] = false;
            }

            $this->logToolExecuted($name, $user, $company, 1);

            return $summary;
        }

        if ($name === 'mi_products_needing_cop' && $this->canReadMiCatalog($user, $company)) {
            if (! Schema::hasColumn('mi_products', 'country_of_production')) {
                $this->logToolExecuted($name, $user, $company, 0);

                return ['available' => false, 'reason' => 'Country-of-production tracking is not installed in this database.'];
            }

            $result = MI_Product::query()->where(function ($query): void {
                $query->whereNull('country_of_production')->orWhereRaw("TRIM(country_of_production) = ''");
            })->orderBy('item_name')->limit(25)->get(['product_id', 'item_name', 'sku', 'item_code'])
                ->map(fn (MI_Product $product): array => ['id' => $product->product_id, 'name' => $product->item_name, 'sku' => $product->sku ?: $product->item_code])
                ->all();
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'mmc_operations_snapshot' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            $result = $this->jarvisReadService->dashboard([]);
            $this->logToolExecuted($name, $user, $company, 1);

            return $result;
        }

        if ($name === 'my_mi_budget_requests' && $company->code === 'MI' && $user->hasMIUserAccess()) {
            $result = BudgetRequest::query()->where('company_id', $company->getKey())->where('employee_id', $user->getKey())
                ->latest('id')->limit(10)->get(['control_id', 'department', 'budget_total', 'status', 'travel_date_from', 'travel_date_to'])
                ->map(fn (BudgetRequest $budgetRequest): array => $budgetRequest->only(['control_id', 'department', 'budget_total', 'status', 'travel_date_from', 'travel_date_to']))->all();
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'mi_accounting_queue_summary' && $company->code === 'MI' && $user->hasAccountingRole() && $user->can('mi.accounting.dashboard.view')) {
            $result = $this->accountingWorkspace->counters($user);
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'mi_pending_approval_summary' && $company->code === 'MI' && ($user->canApproveMI('mi.budget.approve') || $user->canApproveMI('mi.travel.approve'))) {
            $queries = $this->approvalQueue->queries($user);
            $result = [
                'pending_budget_approvals' => $this->approvalQueue->status($queries['budgets'], 'budgets', 'pending')->count(),
                'pending_travel_liquidations' => $this->approvalQueue->status($queries['travel'], 'travel', 'pending')->count(),
            ];
            $this->logToolExecuted($name, $user, $company, array_sum($result));

            return $result;
        }

        $query = trim((string) ($arguments['query'] ?? ''));
        if ($query === '' || mb_strlen($query) > 120) {
            Log::warning('AI assistant tool input denied', ['tool' => $name, 'user_id' => $user->getKey(), 'company_id' => $company->getKey()]);
            abort(422, 'Search query is invalid.');
        }

        if ($name === 'search_mi_products' && $this->canReadMiCatalog($user, $company)) {
            $result = $this->searchMiProducts($query, false);
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'lookup_mi_product_prices' && $this->canViewMiPrices($user, $company)) {
            $result = $this->searchMiProducts($query, true);
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'search_mmc_projects' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            $result = $this->jarvisReadService->projectRecords(['search' => $query])
                ->orderBy('p.project_id')->limit(8)->get()->map(fn (object $project): array => (array) $project)->all();
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'search_mmc_deliveries' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            $result = $this->jarvisReadService->deliveryRecords(['search' => $query])
                ->orderBy('d.delivery_id')->limit(8)->get()->map(fn (object $delivery): array => (array) $delivery)->all();
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        if ($name === 'search_mmc_inventory' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator', 'Warehouse_officer'])) {
            $result = $this->jarvisReadService->inventoryRecords(['search' => $query])
                ->orderBy('inv.inventory_id')->limit(8)->get()->map(fn (object $inventory): array => (array) $inventory)->all();
            $this->logToolExecuted($name, $user, $company, count($result));

            return $result;
        }

        Log::warning('AI assistant tool denied', [
            'tool' => $name,
            'user_id' => $user->getKey(),
            'company_id' => $company->getKey(),
        ]);
        abort(403, 'This assistant data tool is not available for your account.');
    }

    /** @return array<string, mixed> */
    private function productSearchTool(string $name, string $description): array
    {
        return $this->searchTool($name, $description, 'Product name, SKU, item code, category, collection, material, or color');
    }

    /** @return array<string, mixed> */
    private function searchTool(string $name, string $description, string $queryDescription): array
    {
        return ['type' => 'function', 'name' => $name, 'description' => $description, 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => $queryDescription]], 'required' => ['query'], 'additionalProperties' => false], 'strict' => true];
    }

    /** @return array<int, array<string, mixed>> */
    private function searchMiProducts(string $query, bool $includePrice): array
    {
        $terms = array_values(array_unique(preg_split('/[\s\W_]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: []));
        $columns = ['product_id', 'item_name', 'sku', 'item_code', 'category_id', 'sub_category_id', 'collection_id', 'materials', 'color'];
        if ($includePrice) {
            $columns[] = 'price';
        }
        $products = MI_Product::query()->with(['images', 'category', 'collection'])->where(function ($builder) use ($query, $terms): void {
            $builder->where('item_name', 'like', '%'.$query.'%')
                ->orWhere('sku', 'like', '%'.$query.'%')
                ->orWhere('item_code', 'like', '%'.$query.'%')
                ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$query.'%')->orWhere('code', 'like', '%'.$query.'%'))
                ->orWhereHas('collection', fn ($collection) => $collection->where('name', 'like', '%'.$query.'%')->orWhere('code', 'like', '%'.$query.'%'));

            if (count($terms) > 1) {
                $builder->orWhere(function ($allTerms) use ($terms): void {
                    foreach ($terms as $term) {
                        $allTerms->where(function ($oneTerm) use ($term): void {
                            $oneTerm->where('item_name', 'like', '%'.$term.'%')
                                ->orWhere('sku', 'like', '%'.$term.'%')
                                ->orWhere('item_code', 'like', '%'.$term.'%')
                                ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%'))
                                ->orWhereHas('collection', fn ($collection) => $collection->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%'));
                        });
                    }
                });
            }
        });

        return $products->orderByRaw('CASE WHEN LOWER(item_name) = LOWER(?) OR LOWER(sku) = LOWER(?) OR LOWER(item_code) = LOWER(?) THEN 0 WHEN LOWER(item_name) LIKE LOWER(?) OR LOWER(sku) LIKE LOWER(?) OR LOWER(item_code) LIKE LOWER(?) THEN 1 ELSE 2 END', [$query, $query, $query, '%'.$query.'%', '%'.$query.'%', '%'.$query.'%'])
            ->orderBy('item_name')->limit(8)
            ->get($columns)
            ->map(fn (MI_Product $product): array => $this->productPresenter->present($product, $includePrice))
            ->all();
    }

    private function canReadMiCatalog(User $user, Company $company): bool
    {
        return $company->code === 'MI' && $user->can('ai.assistant.use') && $user->can('mi.product-catalog.view') && $this->isActiveCompany($user, $company);
    }

    private function canViewMiPrices(User $user, Company $company): bool
    {
        return $this->canReadMiCatalog($user, $company) && $user->can('mi.product-prices.view');
    }

    private function logToolExecuted(string $name, User $user, Company $company, int $recordCount): void
    {
        Log::info('AI assistant tool executed', [
            'tool' => $name,
            'user_id' => $user->getKey(),
            'company_id' => $company->getKey(),
            'record_count' => $recordCount,
        ]);
    }

    private function isActiveCompany(User $user, Company $company): bool
    {
        return $user->can('ai.assistant.use')
            && (bool) $company->is_active
            && $user->currentCompany()?->getKey() === $company->getKey()
            && $user->belongsToCompany($company->getKey());
    }
}
