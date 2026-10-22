<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MmcPplSourceLinkService;
use Illuminate\Console\Command;

class LinkHistoricalMmcPplSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:link-historical-mmc-ppl-sources {--apply : Persist only uniquely verified matches} {--confirmed-by= : User ID authorizing application of verified links}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dry-run historical MMC PPL source linking; --apply persists uniquely verified matches.';

    /**
     * Execute the console command.
     */
    public function handle(MmcPplSourceLinkService $service): int
    {
        if ($this->option('apply')) {
            $userId = $this->option('confirmed-by');
            if (! is_numeric($userId)) {
                $this->error('Pass --confirmed-by with the authorizing MMC Finance user ID to apply links.');

                return self::FAILURE;
            }
            $user = User::query()->findOrFail((int) $userId);
            if (! $user->hasRole('finance')) {
                $this->error('The selected user must have the Finance role.');

                return self::FAILURE;
            }
            $report = $service->linkVerifiedHistoricalSources($user);
        } else {
            $report = $service->dryRunHistoricalLinks();
        }

        $this->table(
            ['PPL Form', 'Project Code', 'Lot', 'Status', 'Project', 'Bidding', 'Candidates (ID, name, identifier)', 'Bidding Lot', 'Legacy Lot', 'Action / Reasons'],
            collect($report['rows'])->map(fn (array $row): array => [
                $row['ppl_form_id'], $row['project_code'], $row['lot_number'], $row['status'], $row['project_id'],
                $row['bidding_id'], collect($row['candidate_biddings'] ?? [])->map(fn (array $candidate): string => $candidate['id'].': '.$candidate['name'].' ['.$candidate['identifier'].']')->implode('; '),
                $row['bidding_lot_id'], $row['legacy_lot_id'],
                $row['action'] ?? implode('; ', $row['reasons'] ?? []),
            ])->all(),
        );
        $this->line('Summary: '.json_encode($report['summary'] ?? ['linked' => $report['linked'], 'skipped' => $report['skipped']]));

        return self::SUCCESS;
    }
}
