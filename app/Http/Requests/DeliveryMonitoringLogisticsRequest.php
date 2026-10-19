<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryMonitoringLogisticsRequest extends FormRequest
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
        return [
            'project_id' => ['nullable', 'integer', 'min:1'],
            'lot_id' => ['nullable', 'integer', 'min:1'],
            'integration_id' => ['nullable', 'string', 'max:64'],
            'dr_reference' => ['nullable', 'string', 'max:100'],
            'school' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['logistics_received', 'delivered'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', Rule::in([10, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
