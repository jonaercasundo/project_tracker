<?php

namespace App\Console\Commands;

use App\Services\LogisticsWebhookEventService;
use Illuminate\Console\Command;

class RecoverLogisticsWebhookEvents extends Command
{
    protected $signature = 'logistics:webhooks:recover {--limit=100} {--retry-failed}';

    protected $description = 'Queue stored logistics webhook events that have not been queued.';

    public function handle(LogisticsWebhookEventService $events): int
    {
        $result = $events->recoverUnqueued(
            (int) $this->option('limit'),
            (bool) $this->option('retry-failed')
        );

        $this->info("Queued {$result['queued']} event(s); {$result['failed']} could not be queued.");

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
