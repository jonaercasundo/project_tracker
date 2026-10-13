<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('user') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::itemRules();
    }

    /** @return array<string, array<int, mixed>> */
    public static function itemRules(): array
    {
        return [
            'code_prefix' => ['required', 'string', 'max:255'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'supplier_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $prefix = $this->input('code_prefix');

        if (is_string($prefix)) {
            $this->merge(['code_prefix' => mb_strtoupper(trim($prefix))]);
        }
    }
}
