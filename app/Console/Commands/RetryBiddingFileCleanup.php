<?php

namespace App\Console\Commands;

use App\Services\BiddingDocumentService;
use Illuminate\Console\Command;

class RetryBiddingFileCleanup extends Command
{
    protected $signature = 'bidding:cleanup-files {--limit=100 : Maximum pending files to process}';

    protected $description = 'Retry durable cleanup of deleted bidding document files';

    public function handle(BiddingDocumentService $documents): int
    {
        $result = $documents->purgePendingFileDeletions((int) $this->option('limit'));
        $this->info('Removed '.$result['removed'].' files; '.$result['failed'].' remain for retry.');

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
