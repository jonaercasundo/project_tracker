<?php

namespace App\Http\Requests;

class UpdateBiddingFolderRequest extends StoreBiddingFolderRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/u'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
