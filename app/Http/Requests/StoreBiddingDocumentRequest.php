<?php

namespace App\Http\Requests;

class StoreBiddingDocumentRequest extends BiddingDocumentRequest
{
    protected function ability(): string
    {
        return 'uploadDocument';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.config('bidding.documents.max_files')],
            'files.*' => $this->fileRules(),
            'folder_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
