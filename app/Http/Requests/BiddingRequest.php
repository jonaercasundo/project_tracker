<?php

namespace App\Http\Requests;

use App\Models\ProjectInformation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class BiddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bidding = $this->route('bidding');

        return $bidding instanceof ProjectInformation
            ? Gate::allows('update', $bidding)
            : Gate::allows('create', ProjectInformation::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('hierarchy_present') && ! $this->has('lots')) {
            $this->merge(['lots' => []]);
        }

        $lots = $this->input('lots');
        if (! is_array($lots)) {
            return;
        }

        $this->merge(['lots' => $this->restoreEmptyCollections($lots)]);
    }

    /** @param array<array-key, mixed> $rows @return array<array-key, mixed> */
    private function restoreEmptyCollections(array $rows): array
    {
        foreach ($rows as &$row) {
            if (! is_array($row)) {
                continue;
            }
            foreach (['addresses', 'keystages', 'items', 'legacy_items'] as $collection) {
                if (in_array($row[$collection.'_present'] ?? null, ['1', 1, true], true)) {
                    $row[$collection] ??= [];
                }
                if (isset($row[$collection]) && is_array($row[$collection])) {
                    $row[$collection] = $this->restoreEmptyCollections($row[$collection]);
                }
            }
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $updating = $this->route('bidding') instanceof ProjectInformation;
        $required = $updating ? 'sometimes' : 'required';
        $unique = Rule::unique('project_information', 'project_id');
        if ($updating) {
            $unique->ignore($this->route('bidding')->getKey());
        }

        $rules = [
            'hierarchy_present' => ['sometimes', 'boolean'],
            'hierarchy_complete' => ['required_if:hierarchy_present,1', 'boolean'],
            'project_code' => [$required, 'required', Rule::in(['SME', 'SFP', 'MT', 'Textbook', 'DCP'])],
            'project_name' => [$required, 'required', 'string', 'max:255'],
            'project_id' => [$required, 'required', 'string', 'max:255', $unique],
            'procuring_entity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'approved_budget_contract_abc' => ['sometimes', 'nullable', ...$this->decimalRules()],
            'delivery_period' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:36500'],
            'date_of_pre_bid_conference' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_of_bid_opening' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'prepared_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'prepared_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'verified_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes_special_condition' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'required', Rule::in(['Draft', 'For Review', 'Published', 'Awarded', 'Cancelled', 'Completed'])],
            'lots' => [$required, 'array', 'min:1', 'max:20'],
            'lots.*' => ['array:id,lot_no,country_code,region_code,province_code,city_code,barangay_code,addresses,addresses_present,legacy_items,legacy_items_present'],
            'lots.*.id' => ['sometimes', 'integer', 'min:1', 'distinct'],
            'lots.*.lot_no' => ['required', 'string', 'max:50', 'distinct'],
            'lots.*.country_code' => ['sometimes', Rule::in(['PH'])],
            'lots.*.addresses_present' => ['sometimes', 'boolean'],
            'lots.*.legacy_items_present' => ['sometimes', 'boolean'],
            'lots.*.addresses' => ['sometimes', 'array', 'max:20'],
            'lots.*.addresses.*' => ['array:id,delivery_address,keystages,keystages_present'],
            'lots.*.addresses.*.id' => ['sometimes', 'integer', 'min:1', 'distinct'],
            'lots.*.addresses.*.delivery_address' => ['required', 'string', 'max:2000'],
            'lots.*.addresses.*.keystages_present' => ['sometimes', 'boolean'],
            'lots.*.addresses.*.keystages' => ['sometimes', 'array', 'max:20'],
            'lots.*.addresses.*.keystages.*' => ['array:id,name,items,items_present'],
            'lots.*.addresses.*.keystages.*.id' => ['sometimes', 'integer', 'min:1', 'distinct'],
            'lots.*.addresses.*.keystages.*.name' => ['required', 'string', 'max:255'],
            'lots.*.addresses.*.keystages.*.items_present' => ['sometimes', 'boolean'],
            'lots.*.addresses.*.keystages.*.items' => ['sometimes', 'array', 'max:100'],
            'lots.*.legacy_items' => ['sometimes', 'array', 'max:100'],
        ];

        foreach (['region_code' => 'Reg', 'province_code' => 'Prov', 'city_code' => null, 'barangay_code' => 'Bgy'] as $field => $level) {
            $exists = Rule::exists('psgc', 'psgc_code');
            if ($level !== null) {
                $exists->where('geographic_level', $level);
            } else {
                $exists->where(fn ($query) => $query->whereIn('geographic_level', ['City', 'Mun', 'SubMun']));
            }
            $rules['lots.*.'.$field] = ['sometimes', 'nullable', 'string', 'max:10', $exists];
        }

        foreach (['lots.*.addresses.*.keystages.*.items' => false, 'lots.*.legacy_items' => true] as $path => $legacy) {
            $rules[$path.'.*'] = ['array:id,catalog_item_id,item_description,unit,quantity,unit_cost,total_amount,remarks,brand'];
            $rules[$path.'.*.id'] = [$legacy ? 'required' : 'sometimes', 'integer', 'min:1', 'distinct'];
            $rules[$path.'.*.catalog_item_id'] = [$legacy ? 'nullable' : 'required', 'integer', Rule::exists('items', 'id')];
            $rules[$path.'.*.item_description'] = ['sometimes', 'nullable', 'string', 'max:10000'];
            $rules[$path.'.*.unit'] = ['sometimes', 'nullable', 'string', 'max:50'];
            $rules[$path.'.*.quantity'] = ['required', ...$this->decimalRules()];
            $rules[$path.'.*.unit_cost'] = [$legacy ? 'nullable' : 'required', ...$this->decimalRules()];
            $rules[$path.'.*.total_amount'] = ['sometimes', 'nullable', ...$this->decimalRules()];
            $rules[$path.'.*.remarks'] = ['sometimes', 'nullable', 'string', 'max:10000'];
            $rules[$path.'.*.brand'] = ['sometimes', 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /** @return list<string> */
    private function decimalRules(): array
    {
        return ['numeric', 'regex:/^\d{1,13}(?:\.\d{1,2})?$/', 'min:0'];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $itemCount = 0;
            foreach ($this->input('lots', []) as $index => $lot) {
                foreach (['province_code' => 'region_code', 'city_code' => empty($lot['province_code']) ? 'region_code' : 'province_code', 'barangay_code' => 'city_code'] as $child => $parent) {
                    if (empty($lot[$child])) {
                        continue;
                    }
                    if (empty($lot[$parent]) || ! DB::table('psgc')->where('psgc_code', $lot[$child])->where($parent, $lot[$parent])->exists()) {
                        $validator->errors()->add('lots.'.$index.'.'.$child, 'Select a location belonging to its selected parent.');
                    }
                }
                $itemCount += count($lot['legacy_items'] ?? []);
                foreach ($lot['addresses'] ?? [] as $address) {
                    foreach ($address['keystages'] ?? [] as $stage) {
                        $itemCount += count($stage['items'] ?? []);
                    }
                }
            }
            if ($itemCount > 1000) {
                $validator->errors()->add('lots', 'A bidding document may contain at most 1,000 items.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'project_id.unique' => 'A bidding document with this Project ID already exists.',
            'hierarchy_complete.required_if' => 'The form was truncated. Reduce its size or ask your administrator to increase the server input limit; nothing was saved.',
            '*.regex' => 'Use a nonnegative amount with at most 13 integer digits and two decimal places.',
        ];
    }
}
