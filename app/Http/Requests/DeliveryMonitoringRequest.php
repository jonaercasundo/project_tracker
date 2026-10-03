<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryMonitoringRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::filterRules();
    }

    /** @return array<string, array<int, mixed>> */
    public static function filterRules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'delivery_status' => ['nullable', Rule::in(['pending', 'released', 'delivered', 'accepted', 'warehouse', 'mixed', 'cancelled', 'for approval'])],
            'region' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'active_only' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['project', 'progress', 'total_drs', 'total_dr_packages', 'last_delivery', 'end_date'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
