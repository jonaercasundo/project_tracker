<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JarvisReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->query();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = [
            'company_id' => ['sometimes', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'search' => ['sometimes', 'string', 'min:1', 'max:200'],
            'project_id' => ['sometimes', 'integer', 'min:1'],
            'year' => ['sometimes', 'integer', 'between:1900,2100'],
            'status' => ['sometimes', 'string', 'max:50'],
            'agency' => ['sometimes', 'string', 'max:255'],
            'ref_no' => ['sometimes', 'string', 'max:50'],
            'has_pending_deliveries' => ['sometimes', 'boolean'],
            'lot_id' => ['sometimes', 'integer', 'min:1'],
            'lot_name' => ['sometimes', 'string', 'max:100'],
            'warehouse_id' => ['sometimes', 'integer', 'min:1'],
            'region' => ['sometimes', 'string', 'max:255'],
            'division' => ['sometimes', 'string', 'max:255'],
            'municipality' => ['sometimes', 'string', 'max:255'],
            'delivery_status' => ['sometimes', Rule::in(['pending', 'delivered', 'accepted', 'cancelled', 'warehouse', 'for approval'])],
            'package_status' => ['sometimes', Rule::in(['pending', 'accepted', 'delivered', 'warehouse', 'released'])],
            'billing_status' => ['sometimes', Rule::in(['for billing', 'billed', 'paid', 'unknown'])],
            'date_field' => ['sometimes', Rule::in(['delivery_date', 'delivered_date', 'accepted_date', 'created_at'])],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => array_merge(['sometimes', 'date_format:Y-m-d'], $this->query->has('date_from') ? ['after_or_equal:date_from'] : []),
            'inventory_status' => ['sometimes', Rule::in(['For Approval', 'Approved'])],
            'available_only' => ['sometimes', 'boolean'],
            'item_id' => ['sometimes', 'integer', 'min:1'],
            'package_id' => ['sometimes', 'integer', 'min:1'],
            'delivery_id' => ['sometimes', 'integer', 'min:1'],
            'source' => ['sometimes', Rule::in(['catalog', 'operations'])],
            'catalog_project_id' => ['sometimes', 'string', 'max:255'],
            'catalog_lot_id' => ['sometimes', 'integer', 'min:1'],
            'active' => ['sometimes', 'boolean'],
            'code_prefix' => ['sometimes', 'string', 'max:255'],
        ];

        if ($this->route()->getActionMethod() === 'deliveryProgress') {
            $rules = array_replace($rules, DeliveryMonitoringRequest::filterRules());
            $rules['per_page'] = ['sometimes', 'integer', 'between:1,50'];
            $rules['compact'] = ['sometimes', 'boolean'];
        }

        return array_intersect_key($rules, array_flip($this->supportedFilters()));
    }

    /** @return list<string> */
    public function supportedFilters(): array
    {
        $projects = ['project_id', 'year', 'status', 'agency', 'ref_no', 'search', 'has_pending_deliveries'];
        $deliveries = ['project_id', 'lot_id', 'region', 'division', 'municipality', 'delivery_status', 'package_status', 'billing_status', 'year', 'date_field', 'date_from', 'date_to', 'warehouse_id', 'delivery_id', 'search'];
        $inventory = ['project_id', 'warehouse_id', 'item_id', 'inventory_status', 'available_only', 'search'];
        $endpoint = $this->route()->getActionMethod();
        $filters = match ($endpoint) {
            'dashboard' => ['project_id', 'year', 'status', 'agency', 'ref_no'],
            'projects' => $projects,
            'deliveryProgress' => array_merge(array_keys(DeliveryMonitoringRequest::filterRules()), ['page', 'per_page', 'status', 'lot_id', 'compact']),
            'project' => [],
            'deliveries' => $deliveries,
            'inventory', 'warehouses' => $inventory,
            'lots' => ['project_id', 'lot_id', 'lot_name', 'search'],
            'packages' => array_merge($deliveries, ['package_id']),
            'masterlist' => $this->query('source', 'catalog') === 'operations'
                ? ['source', 'project_id', 'lot_id', 'package_id', 'item_id', 'search']
                : ['source', 'catalog_project_id', 'catalog_lot_id', 'active', 'code_prefix', 'search'],
            default => [],
        };

        return array_values(array_unique(array_merge(['company_id'], in_array($endpoint, ['dashboard', 'project', 'deliveryProgress'], true) ? [] : ['page', 'per_page'], $filters)));
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->query()), $this->supportedFilters()) as $filter) {
                $validator->errors()->add($filter, 'Unsupported filter for this endpoint.');
            }
        }];
    }
}
