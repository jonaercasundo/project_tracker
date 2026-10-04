<?php

namespace App\Http\Requests;

class UpdateBiddingDocumentRequest extends BiddingDocumentRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'display_name' => array_merge(['sometimes', 'required'], $this->displayNameRules()),
            'folder_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
