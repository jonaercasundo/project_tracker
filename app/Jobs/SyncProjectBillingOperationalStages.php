<?php

namespace App\Jobs;

use App\Services\BillingOperationalSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncProjectBillingOperationalStages implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $syncRunId)
    {
        $this->onConnection('billing_sync')->onQueue('billing_sync');
    }

    public function handle(BillingOperationalSyncService $sync): void
    {
        $claimed = DB::table('billing_sync_runs')
            ->where('id', $this->syncRunId)
            ->where('status', 'queued')
            ->update([
                'status' => 'running',
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $run = DB::table('billing_sync_runs')->where('id', $this->syncRunId)->first();
        if ($run === null) {
            return;
        }

        try {
            $result = $sync->syncProject(
                (int) $run->project_id,
                (int) $run->user_id,
                (string) $run->user_name
            );

            DB::table('billing_sync_runs')->where('id', $this->syncRunId)->update([
                'status' => 'completed',
                'message' => $result['message'],
                'synced_dr_count' => $result['synced_dr_count'],
                'skipped_dr_count' => $result['skipped_dr_count'],
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (RuntimeException $exception) {
            DB::table('billing_sync_runs')->where('id', $this->syncRunId)->update([
                'status' => 'failed',
                'message' => $exception->getMessage().' No changes were saved.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Queued Sync Billing failed.', [
                'billing_sync_run_id' => $this->syncRunId,
                'project_id' => $run->project_id,
                'user_id' => $run->user_id,
                'exception' => $exception,
            ]);

            DB::table('billing_sync_runs')->where('id', $this->syncRunId)->update([
                'status' => 'failed',
                'message' => 'Sync Billing could not complete. No changes were saved. Please contact an administrator.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('billing_sync_runs')->where('id', $this->syncRunId)
            ->whereIn('status', ['queued', 'running'])
            ->update([
                'status' => 'failed',
                'message' => 'Sync Billing worker could not complete the request. No changes were saved. Please try again or contact an administrator.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::error('Queued Sync Billing exhausted its queue attempt.', [
            'billing_sync_run_id' => $this->syncRunId,
            'exception' => $exception,
        ]);
    }
}
