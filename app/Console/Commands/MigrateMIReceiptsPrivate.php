<?php

namespace App\Console\Commands;

use App\Models\FinancialActivity;
use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateMIReceiptsPrivate extends Command
{
    protected $signature = 'mi:migrate-receipts-private {--company= : Verified company ID} {--execute : Copy and update references} {--dry-run : Inspect without writes}';

    protected $description = 'Copy verified legacy receipt references to private storage; retain every public source.';

    public function handle(): int
    {
        $company = (string) $this->option('company');
        if (! is_string($company) || ! ctype_digit($company) || (int) $company < 1) {
            $this->error('A verified --company ID is required.');

            return self::FAILURE;
        }
        $dryRun = ! $this->option('execute') || $this->option('dry-run');
        $failed = 0;
        MI_LiquidationItem::whereNotNull('receipt_image')->orderBy('id')->chunkById(100, function ($items) use ($company, $dryRun, &$failed): void {
            foreach ($items as $item) {
                try {
                    $result = DB::transaction(function () use ($item, $company, $dryRun): string {
                        $report = MI_Liquidation::withTrashed()->whereKey($item->liquidation_id)->lockForUpdate()->first();
                        if (! $report || (int) $report->company_id !== (int) $company) {
                            return 'skipped: outside verified company';
                        }
                        $locked = MI_LiquidationItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();
                        $source = $locked->receipt_image;
                        if (str_starts_with($source, 'private/')) {
                            return 'skipped: already private';
                        }
                        if (! preg_match('~^liquidations/receipts/[A-Za-z0-9._-]+$~D', $source)) {
                            throw new \RuntimeException('Unsafe receipt reference.');
                        }
                        if (! Storage::disk('public')->exists($source)) {
                            throw new \RuntimeException('Source missing.');
                        }
                        if ($dryRun) {
                            return 'dry-run: eligible';
                        }
                        $bytes = Storage::disk('public')->get($source);
                        $digest = hash('sha256', $bytes);
                        $destination = 'private/liquidations/receipts/legacy-'.$locked->getKey().'-'.$digest.'.'.pathinfo($source, PATHINFO_EXTENSION);
                        if (! Storage::disk('local')->exists($destination)) {
                            if (! Storage::disk('local')->put($destination, $bytes)) {
                                throw new \RuntimeException('Private copy failed.');
                            }
                        }
                        if (! hash_equals($digest, hash('sha256', Storage::disk('local')->get($destination)))) {
                            throw new \RuntimeException('Private verification failed.');
                        }
                        $locked->update(['receipt_image' => $destination]);
                        FinancialActivity::create([
                            'company_id' => $report->company_id, 'record_type' => $report->getTable(), 'record_id' => $report->getKey(),
                            'event' => 'receipt_migrated_private', 'previous_status' => $report->status, 'new_status' => $report->status,
                            'actor_name_snapshot' => 'Receipt migration command',
                            'metadata' => ['item_id' => $locked->getKey(), 'sha256' => $digest, 'public_source_retained' => true],
                        ]);

                        return 'migrated: private copy verified; public source retained';
                    });
                    $this->line('Item '.$item->getKey().' '.$result);
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error('Item '.$item->getKey().' failed; reference unchanged.');
                }
            }
        });

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
