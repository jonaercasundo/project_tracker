<?php

namespace App\Console\Commands;

use App\Services\LogisticsDeliveredProjection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\OutputInterface;

class ReconcileLogisticsDelivered extends Command
{
    protected $signature = 'logistics:reconcile-delivered {--through-id= : Highest webhook row ID to include}
        {--apply : Apply and audit the reported projection changes} {--actor= : Operator name for the audit trail}';

    protected $description = 'Report or reconcile resolved Airspeed Delivered webhooks into Logistics tracking';

    public function handle(LogisticsDeliveredProjection $projection): int
    {
        $apply = (bool) $this->option('apply');
        $actor = trim((string) $this->option('actor'));
        $throughId = $this->option('through-id');

        if (($throughId !== null && (! ctype_digit($throughId) || (int) $throughId < 1))
            || ($apply && ($throughId === null || $actor === ''))
        ) {
            $this->error('Provide a positive --through-id and, when applying, an --actor.');

            return self::FAILURE;
        }

        $events = DB::table('logistics_webhook_events')
            ->whereIn('integration_id', LogisticsDeliveredProjection::INTEGRATIONS)
            ->where('event_type', 'delivery.delivered')
            ->where('processing_status', 'processed');

        $throughId ??= (int) (clone $events)->max('id');
        $events->where('id', '<=', $throughId);
        $unresolved = (clone $events)->where(function ($query): void {
            $query->whereNull('resolution_status')->orWhere('resolution_status', '<>', 'resolved');
        })->count();
        $counts = ['insert' => 0, 'update' => 0];
        $seen = [];

        foreach ($events->where('resolution_status', 'resolved')->orderByDesc('event_occurred_at')
            ->orderByDesc('id')->get(['id', 'integration_id', 'resolved_project_id', 'resolved_school_id', 'dr_reference']) as $event) {
            $scope = json_encode([$event->integration_id, $event->resolved_project_id, $event->resolved_school_id, $event->dr_reference], JSON_THROW_ON_ERROR);
            if (isset($seen[$scope])) {
                continue;
            }

            $seen[$scope] = true;
            $change = $projection->reconcile((int) $event->id, $actor, $apply);
            if ($change !== null) {
                $counts[$change['action']]++;
                $this->output->writeln(json_encode($change, JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            }
        }

        $mode = $apply ? 'Applied' : 'Dry run';
        $this->info("{$mode} through webhook row {$throughId}. Inserts: {$counts['insert']}, updates: {$counts['update']}, unresolved: {$unresolved}.");

        return self::SUCCESS;
    }
}
