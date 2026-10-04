<?php

namespace App\Http\Requests;

use Closure;

class IndexBiddingDocumentsRequest extends BiddingDocumentRequest
{
    protected function ability(): string
    {
        return 'view';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== 'root' && (! is_scalar($value) || ! ctype_digit((string) $value) || (int) $value < 1)) {
                    $fail('Choose a valid folder.');
                }
            }],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
