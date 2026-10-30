<?php

namespace App\Http\Requests;

use App\Models\Delivery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryReturnRequest extends FormRequest
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
            'return_type' => ['required', Rule::in(['Item Return', 'Document Return'])],
            'delivery_id' => ['required', 'integer', Rule::exists(Delivery::class, 'delivery_id')->where('project_id', $this->route('project'))],
            'package_id' => ['nullable', 'integer', Rule::exists('package', 'package_id')],
            'item_id' => ['required_if:return_type,Item Return', 'nullable', 'integer', Rule::exists('item', 'item_id')],
            'quantity_returned' => ['required_if:return_type,Item Return', 'nullable', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'monitoring_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['Reported', 'In Transit'])],
        ];
    }
}
