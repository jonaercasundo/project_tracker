<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MIApprovalReview
{
    public static function fingerprint(Model $record): string
    {
        return hash('sha256', json_encode([
            $record->getAttributes(),
            $record->items()->orderBy('id')->get()->toArray(),
            $record instanceof \App\Models\Liquidation ? $record->budgetRequest->getAttributes() : null,
        ], JSON_THROW_ON_ERROR));
    }

    public function mark(Request $request, Model $record): void
    {
        $request->session()->put($this->key($record), self::fingerprint($record));
    }

    public function reviewedVersion(Request $request, Model $record): string
    {
        $version = $request->session()->get($this->key($record));
        abort_unless(is_string($version), 422, 'Review the transaction details before making an approval decision.');

        return $version;
    }

    private function key(Model $record): string
    {
        return 'mi_approval_reviews.'.$record->getTable().'.'.$record->getKey();
    }
}
