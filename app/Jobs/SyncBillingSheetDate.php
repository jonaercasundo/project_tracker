<?php

namespace App\Jobs;

use App\Models\BillingSheetSync;
use App\Services\BillingSheetSyncService;
use App\Services\GoogleSheetsApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncBillingSheetDate implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $billingSheetSyncId)
    {
        $this->onQueue('default');
    }

    public function handle(BillingSheetSyncService $syncService, GoogleSheetsApiClient $sheets): void
    {
        $sync = BillingSheetSync::query()->findOrFail($this->billingSheetSyncId);
        if (! in_array($sync->outcome, ['queued', 'retrying', 'dispatch_failed', 'queue_unavailable'], true)) {
            return;
        }

        try {
            $syncService->sync($sync, $sheets);
        } catch (Throwable $exception) {
            $sync->refresh()->forceFill([
                'outcome' => 'retrying',
                'error_details' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $sync = BillingSheetSync::query()->find($this->billingSheetSyncId);
        if ($sync === null) {
            return;
        }

        $sync->forceFill([
            'outcome' => 'api_failed',
            'error_details' => mb_substr($exception?->getMessage() ?? 'Google Sheets queue job failed.', 0, 2000),
        ])->save();
        Log::error('Billing Google Sheets sync exhausted its queue retries.', [
            'billing_sheet_sync_id' => $sync->getKey(),
            'delivery_id' => $sync->delivery_id,
            'package_status_id' => $sync->package_status_id,
            'exception' => $exception,
        ]);
    }
}
