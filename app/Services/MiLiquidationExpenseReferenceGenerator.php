<?php

namespace App\Services;

use App\Models\MI_LiquidationItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MiLiquidationExpenseReferenceGenerator
{
    /**
     * Allocate the next reference for the supplied liquidation date.
     */
    public function next(string $date): string
    {
        $referenceDate = Carbon::parse($date)->format('Ymd');

        return DB::transaction(function () use ($referenceDate): string {
            DB::table('mi_liquidation_reference_sequences')->insertOrIgnore([
                'reference_date' => $referenceDate,
                'next_sequence' => 1,
            ]);

            $sequence = DB::table('mi_liquidation_reference_sequences')
                ->where('reference_date', $referenceDate)
                ->lockForUpdate()
                ->first();

            $references = MI_LiquidationItem::query()
                ->where('ref_no', 'like', 'LF-'.$referenceDate.'-%')
                ->pluck('ref_no');
            $highestExistingSequence = 0;

            foreach ($references as $reference) {
                if (preg_match('/^LF-'.preg_quote($referenceDate, '/').'-(\d+)$/', (string) $reference, $matches) === 1) {
                    $highestExistingSequence = max($highestExistingSequence, (int) $matches[1]);
                }
            }

            $nextSequence = max((int) $sequence->next_sequence, $highestExistingSequence + 1);

            DB::table('mi_liquidation_reference_sequences')
                ->where('reference_date', $referenceDate)
                ->update(['next_sequence' => $nextSequence + 1]);

            return sprintf('LF-%s-%03d', $referenceDate, $nextSequence);
        }, 5);
    }
}
