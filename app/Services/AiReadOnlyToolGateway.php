<?php

namespace App\Services;

use App\Models\BudgetRequest;
use App\Models\Company;
use App\Models\MI_Product;
use App\Models\User;

class AiReadOnlyToolGateway
{
    /** @return array<int, array<string, mixed>> */
    public function tools(User $user, Company $company, array $context): array
    {
        $tools = [];

        if ($company->code === 'MI' && $user->hasMIUserAccess()) {
            $tools[] = ['type' => 'function', 'name' => 'search_mi_products', 'description' => 'Search the MI product catalog for matching products. Use only when the user asks about saved product records.', 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Product name or code to search']], 'required' => ['query'], 'additionalProperties' => false], 'strict' => true];
        }

        if ($company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            $tools[] = ['type' => 'function', 'name' => 'search_mmc_projects', 'description' => 'Search MMC project records visible in the active MMC company.', 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Project name, code, or procuring entity']], 'required' => ['query'], 'additionalProperties' => false], 'strict' => true];
        }

        if ($company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            $tools[] = ['type' => 'function', 'name' => 'mmc_operations_snapshot', 'description' => 'Get calculated MMC project, delivery, package, and inventory totals for the active company workspace.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
            $tools[] = ['type' => 'function', 'name' => 'search_mmc_deliveries', 'description' => 'Search read-only MMC delivery records, receipt statuses, and package counts.', 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Project, receipt number, or school to search']], 'required' => ['query'], 'additionalProperties' => false], 'strict' => true];
        }

        if ($company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator', 'Warehouse_officer'])) {
            $tools[] = ['type' => 'function', 'name' => 'search_mmc_inventory', 'description' => 'Search read-only MMC warehouse inventory by item, warehouse, or project.', 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Item, warehouse, or project to search']], 'required' => ['query'], 'additionalProperties' => false], 'strict' => true];
        }

        if ($company->code === 'MI' && $user->hasMIUserAccess()) {
            $tools[] = ['type' => 'function', 'name' => 'my_mi_budget_requests', 'description' => 'List only the authenticated MI employee’s own budget requests and their status.', 'parameters' => ['type' => 'object', 'properties' => new \stdClass, 'required' => [], 'additionalProperties' => false], 'strict' => true];
        }

        return $tools;
    }

    /** @param array<string, mixed> $arguments
     * @return array<int, array<string, mixed>>|array<string, string>
     */
    public function call(string $name, array $arguments, User $user, Company $company): array
    {
        if ($name === 'mmc_operations_snapshot' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            return app(JarvisReadService::class)->dashboard([]);
        }

        if ($name === 'my_mi_budget_requests' && $company->code === 'MI' && $user->hasMIUserAccess()) {
            return BudgetRequest::query()->where('company_id', $company->getKey())->where('employee_id', $user->getKey())
                ->latest('id')->limit(10)->get(['control_id', 'department', 'budget_total', 'status', 'travel_date_from', 'travel_date_to'])
                ->map(fn (BudgetRequest $budgetRequest): array => $budgetRequest->only(['control_id', 'department', 'budget_total', 'status', 'travel_date_from', 'travel_date_to']))->all();
        }

        $query = trim((string) ($arguments['query'] ?? ''));
        abort_if($query === '' || mb_strlen($query) > 120, 422, 'Search query is invalid.');

        if ($name === 'search_mi_products' && $company->code === 'MI' && $user->hasMIUserAccess()) {
            return MI_Product::query()
                ->where(function ($builder) use ($query): void {
                    $builder->where('item_name', 'like', '%'.$query.'%')->orWhere('item_code', 'like', '%'.$query.'%');
                })
                ->orderBy('item_name')->limit(8)->get(['product_id', 'item_name', 'item_code', 'category_id', 'sub_category_id', 'materials', 'color'])
                ->map(fn (MI_Product $product): array => ['name' => $product->item_name, 'code' => $product->item_code, 'category_id' => $product->category_id, 'sub_category_id' => $product->sub_category_id, 'materials' => $product->materials, 'colors' => $product->color])
                ->all();
        }

        if ($name === 'search_mmc_projects' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            return app(JarvisReadService::class)->projectRecords(['search' => $query])
                ->orderBy('p.project_id')->limit(8)->get()->map(fn (object $project): array => (array) $project)->all();
        }

        if ($name === 'search_mmc_deliveries' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator'])) {
            return app(JarvisReadService::class)->deliveryRecords(['search' => $query])
                ->orderBy('d.delivery_id')->limit(8)->get()->map(fn (object $delivery): array => (array) $delivery)->all();
        }

        if ($name === 'search_mmc_inventory' && $company->code === 'MMC' && $user->hasAnyRole(['user', 'Administrator', 'Warehouse_officer'])) {
            return app(JarvisReadService::class)->inventoryRecords(['search' => $query])
                ->orderBy('inv.inventory_id')->limit(8)->get()->map(fn (object $inventory): array => (array) $inventory)->all();
        }

        abort(403, 'This assistant data tool is not available for your account.');
    }
}
