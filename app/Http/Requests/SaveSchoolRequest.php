<?php

namespace App\Http\Requests;

use App\Models\School;
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
        ];
    }
}
