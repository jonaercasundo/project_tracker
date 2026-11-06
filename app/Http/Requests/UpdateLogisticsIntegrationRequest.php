<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLogisticsIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('integration')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isApiIntegration = $this->route('integration')?->authentication_method !== 'manual';

        return [
            'name' => ['required', 'string', 'max:120'],
            'webhook_url' => $isApiIntegration
                ? ['required', 'url', 'max:500', 'regex:/\Ahttps:\/\//i']
                : ['exclude'],
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['required', 'integer', 'distinct', 'exists:projects,project_id'],
            'school_scopes' => ['nullable', 'array'],
            'school_scopes.*' => ['required', 'string', 'distinct', 'regex:/\A[0-9]+:[A-Za-z0-9_-]+\z/'],
            'region_scopes' => ['nullable', 'array'],
            'region_scopes.*' => ['required', 'string', 'distinct', 'regex:/\A[0-9]+:[A-Za-z0-9_-]+\z/'],
            'all_schools' => ['sometimes', 'accepted'],
            'enabled_event_types' => $isApiIntegration ? ['required', 'array', 'min:1'] : ['exclude'],
            'enabled_event_types.*' => ['required', 'string', 'distinct', Rule::in(config('logistics-webhooks.allowed_event_types', []))],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ];
    }
}
