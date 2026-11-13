<?php

namespace App\Http\Requests;

use App\Models\MI_Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMIProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['materials', 'color'] as $field) {
            $values = $this->input($field);
            if (is_array($values)) {
                $this->merge([$field => array_map(static fn ($value): string => trim((string) $value), $values)]);
            }
        }
        foreach (['image_labels', 'existing_image_labels'] as $field) {
            $values = $this->input($field);
            if (is_array($values)) {
                $this->merge([$field => array_map(static fn ($value): string => trim((string) $value), $values)]);
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('product') instanceof MI_Product
            && Gate::allows('update', $this->route('product'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $ownedImage = Rule::exists('mi_product_images', 'id')->where('product_id', $product->getKey());
        $ownedCost = Rule::exists('mi_product_factory_costs', 'id')->where('product_id', $product->getKey());
        $ownedVariant = Rule::exists('mi_product_variants', 'variant_id')->where('product_id', $product->getKey());
        $rules = [
            'item_name' => 'required|string|max:255',
            'variants_present' => ['sometimes', 'accepted'],
            'item_code' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('mi_products', 'item_code')->ignore($product)],
            'description' => 'sometimes|nullable|string|max:20000',
            'type_of_sample' => 'required|string|max:255',
            'country_of_production' => ['required', Rule::in(['Vietnam', 'China', 'Other'])],
            'custom_country' => ['required_if:country_of_production,Other', 'nullable', 'string', 'max:100'],
            'designed_by' => 'sometimes|nullable|string|max:255',
            'category_id' => 'required|integer|exists:mi_categories,id',
            'sub_category_id' => 'sometimes|required|integer|exists:mi_sub_categories,id',
            'product_type_id' => 'sometimes|nullable|integer|exists:mi_product_types,id',
            'collection_id' => 'sometimes|nullable|integer|exists:mi_collections,id',
            'materials' => 'required_without:variants|array|min:1|max:100',
            'materials.*' => 'required|string|max:255',
            'color' => 'sometimes|nullable|array|max:100',
            'color.*' => 'required|string|max:255',
            'variants' => ['sometimes', 'array', 'max:100'],
            'variants.*' => ['required', 'array:id,sequence,materials,colors,material,color,product_length,product_width,product_height,product_depth,purchase_price,selling_price,sku'],
            'variants.*.id' => ['nullable', 'integer', 'distinct', $ownedVariant],
            'variants.*.sequence' => ['required', 'integer', 'min:1', 'max:9999', 'distinct'],
            'variants.*.materials' => ['required_without:variants.*.material', 'array', 'min:1', 'max:100'],
            'variants.*.materials.*' => ['required', 'string', 'max:255'],
            'variants.*.material' => ['nullable', 'string', 'max:255'],
            'variants.*.colors' => ['sometimes', 'nullable', 'array', 'max:100'],
            'variants.*.colors.*' => ['required', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct'],
            'variants.*.product_length' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.product_width' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.product_height' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.product_depth' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.selling_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'type' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|nullable|numeric|min:0|max:9999999999.99',
            'purchase_cost' => 'sometimes|nullable|numeric|min:0|max:9999999999.99',
            'image_links' => 'nullable|array|max:30',
            'image_links.*' => 'nullable|url:http,https|max:1000',
            'image_labels' => 'nullable|array|max:100',
            'image_labels.*' => ['nullable', 'string', 'max:255'],
            'existing_image_labels' => 'nullable|array|max:100',
            'existing_image_labels.*' => ['nullable', 'string', 'max:255'],
            'product_images' => 'nullable|array|max:30',
            'product_images.*' => 'file|image|mimes:jpeg,png,jpg,webp|max:20480',
            'remove_image_ids' => 'nullable|array|max:100',
            'remove_image_ids.*' => ['integer', 'distinct', $ownedImage],
            'primary_image_id' => ['nullable', 'integer', $ownedImage],
            'image_order' => 'nullable|array|max:100',
            'image_order.*' => ['integer', 'distinct', $ownedImage],
            'factory_costs' => ['sometimes', 'nullable', 'array', 'max:100'],
            'factory_costs.*' => ['required', 'array:id,factory_name,factory_cost,currency,effective_date,remarks,is_primary'],
            'factory_costs.*.id' => ['nullable', 'integer', 'distinct', $ownedCost],
            'factory_costs.*.factory_name' => ['required', 'string', 'max:255'],
            'factory_costs.*.factory_cost' => ['required', 'numeric', 'regex:/\A[0-9]{1,13}(?:\.[0-9]{1,2})?\z/'],
            'factory_costs.*.currency' => ['sometimes', 'required', Rule::in(['PHP'])],
            'factory_costs.*.effective_date' => ['nullable', 'date_format:Y-m-d'],
            'factory_costs.*.remarks' => ['nullable', 'string', 'max:2000'],
            'factory_costs.*.is_primary' => ['sometimes', 'boolean'],
            'remove_factory_cost_ids' => ['sometimes', 'nullable', 'array', 'max:100'],
            'remove_factory_cost_ids.*' => ['required', 'integer', 'distinct', $ownedCost],
        ];
        foreach (['product', 'carton'] as $prefix) {
            foreach (['height', 'width', 'length', 'depth'] as $dimension) {
                $rules[$prefix.'_'.$dimension] = 'sometimes|nullable|numeric|min:0|max:99999999.99';
            }
        }
        foreach ($this->input('variants', []) as $index => $variant) {
            $uniqueSku = Rule::unique('mi_product_variants', 'sku');
            if (! empty($variant['id'])) {
                $uniqueSku->ignore($variant['id'], 'variant_id');
            }
            $rules["variants.{$index}.sku"][] = $uniqueSku;
            $rules["variants.{$index}.sku"][] = Rule::unique('mi_products', 'sku');
        }

        return $rules;
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $costs = collect($this->input('factory_costs') ?? []);
            if ($costs->where('is_primary', true)->count() > 1) {
                $validator->errors()->add('factory_costs', 'Select only one primary factory cost.');
            }

            $removeCostIds = array_map('intval', $this->input('remove_factory_cost_ids') ?? []);
            foreach ($costs as $cost) {
                if (isset($cost['id']) && in_array((int) $cost['id'], $removeCostIds, true)) {
                    $validator->errors()->add('remove_factory_cost_ids', 'A factory cost cannot be edited and removed in the same save.');
                    break;
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'factory_costs.max' => 'A product may have at most 100 factory costs.',
            'factory_costs.*.factory_cost.regex' => 'Enter a non-negative amount with no more than two decimal places.',
            'factory_costs.*.id.exists' => 'This factory cost is unavailable for this product. Reload and try again.',
            'remove_factory_cost_ids.*.exists' => 'This factory cost is unavailable for this product. Reload and try again.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'factory_costs.*.factory_name' => 'factory / supplier name',
            'factory_costs.*.factory_cost' => 'factory cost',
            'factory_costs.*.currency' => 'currency',
            'factory_costs.*.effective_date' => 'effective date',
            'factory_costs.*.remarks' => 'remarks',
            'factory_costs.*.is_primary' => 'primary factory cost selection',
        ];
    }
}
