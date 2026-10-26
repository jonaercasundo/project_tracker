<?php

namespace App\Console\Commands;

use App\Services\AiLiquidationExcelDraftService;
use Illuminate\Console\Command;

class PruneAiAssistantAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-assistant:prune-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired private AI liquidation workbooks and drafts';

    /**
     * Execute the console command.
     */
    public function handle(AiLiquidationExcelDraftService $drafts): int
    {
        $removed = $drafts->purgeExpired();
        $this->info("Removed {$removed} expired AI attachment(s).");

        return Command::SUCCESS;
    }
}
