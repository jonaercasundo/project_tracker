<?php

namespace App\Http\Requests;

class ReplaceBiddingDocumentRequest extends StoreBiddingDocumentRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['file' => $this->fileRules()];
    }
}
