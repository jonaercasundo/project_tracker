<?php

namespace App\Http\Requests;

use App\Models\School;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('user') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = self::schoolRules();
        $school = $this->route('school');
        if ($school instanceof School) {
            $rules['school_id'] = ['sometimes', 'required', 'string', Rule::in([$school->school_id])];
        }

        return $rules;
    }

    /** @return array<string, array<int, mixed>> */
    public static function schoolRules(): array
    {
        return [
            'school_id' => ['required', 'string', 'max:255'],
            'project_id' => [
                'bail', 'nullable', 'regex:/\A[0-9]{1,20}\z/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $digits = ltrim((string) $value, '0');
                    if (strlen($digits) === 20 && strcmp($digits, '18446744073709551615') > 0) {
                        $fail('The project ID must be an unsigned whole number no greater than 18446744073709551615.');
                    }
                },
                Rule::exists('project_information', 'id'),
            ],
            'project_no' => ['nullable', 'string', 'max:50'],
            'project' => ['nullable', 'string', 'max:255'],
            'total_contract_price' => ['nullable', 'regex:/\A-?[0-9]{1,10}(?:\.[0-9]{1,2})?\z/'],
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'division' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'school_id.in' => 'The school ID cannot be changed.',
            'project_id.regex' => 'The project ID must be an unsigned whole number.',
            'project_id.exists' => 'Choose an existing project ID or leave it blank.',
            'total_contract_price.regex' => 'Enter a contract price between -9999999999.99 and 9999999999.99 with at most two decimal places and no thousands separators.',
        ];
    }
}
