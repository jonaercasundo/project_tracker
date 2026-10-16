<?php

namespace App\Console\Commands;

use App\Models\ProjectInformation;
use App\Services\ProjectAwardSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReconcileAwardedProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:reconcile-awards {--apply : Create or link projects for eligible historical awards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preview or safely reconcile historical awarded bidding documents with implementation projects.';

    /**
     * Execute the console command.
     */
    public function handle(ProjectAwardSynchronizationService $synchronization): int
    {
        $awards = ProjectInformation::query()
            ->where('status', 'Awarded')
            ->whereDoesntHave('implementationProject')
            ->orderBy('id');
        $count = (clone $awards)->count();

        if (! $this->option('apply')) {
            $this->info("Dry run: {$count} awarded bidding document(s) are not linked to projects.");
            $this->line('Run with --apply to validate and reconcile each record.');

            return self::SUCCESS;
        }

        $createdOrLinked = 0;
        $failed = 0;
        $awards->chunkById(100, function ($records) use ($synchronization, &$createdOrLinked, &$failed): void {
            foreach ($records as $record) {
                try {
                    DB::transaction(function () use ($record, $synchronization): void {
                        $locked = ProjectInformation::query()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
                        $synchronization->syncFromBidding($locked, true, true, null, null, true);
                    }, 3);
                    $createdOrLinked++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->components->error("Bidding #{$record->getKey()}: {$exception->getMessage()}");
                }
            }
        });

        $this->info("Reconciled {$createdOrLinked} award(s); {$failed} require manual review.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
