<?php

namespace App\Http\Requests;

class DownloadBiddingDocumentsRequest extends BiddingDocumentRequest
{
    protected function ability(): string
    {
        return 'downloadDocument';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'min:1', 'max:'.config('bidding.documents.max_zip_files')],
            'document_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
