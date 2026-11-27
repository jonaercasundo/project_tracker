<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMmcBiometricConnectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('HR') === true
            && $this->user()?->currentCompany()?->code === 'MMC';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'device_ip' => ['required', 'ipv4'],
            'tcp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'device_id' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'device_serial' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
