<?php

namespace App\Console\Commands;

use App\Jobs\SyncMmcPlatformProject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncMmcPlatformMasterlist extends Command
{
    protected $signature = 'mmc-platform:sync-masterlist';

    protected $description = 'Queue a masterlist refresh for each MMC Platform program linked to a Tracker project';

    public function handle(): int
    {
        $snapshots = DB::table('mmc_platform_project_syncs')
            ->whereNotNull('tracker_project_id')
            ->lazyById(100);

        foreach ($snapshots as $snapshot) {
            $companyId = (int) $snapshot->company_id;
            $statusKey = SyncMmcPlatformProject::statusKey($companyId, 0, (string) Str::uuid());

            Bus::dispatch(new SyncMmcPlatformProject($statusKey, $companyId, [
                'program_id' => $snapshot->program_id,
                'tracker_project_id' => (int) $snapshot->tracker_project_id,
                'program_name' => $snapshot->program_name,
                'program_info' => json_decode($snapshot->program_info ?: '{}', true) ?: [],
            ]));
        }

        return Command::SUCCESS;
    }
}
