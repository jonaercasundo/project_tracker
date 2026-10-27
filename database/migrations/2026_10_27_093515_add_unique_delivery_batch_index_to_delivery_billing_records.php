<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_billing_records')
            || ! Schema::hasColumn('delivery_billing_records', 'delivery_id')
            || ! Schema::hasColumn('delivery_billing_records', 'billing_batch_id')) {
            return;
        }

        $columns = ['delivery_id', 'billing_batch_id'];
        if (Schema::hasIndex('delivery_billing_records', $columns, 'unique')) {
            return;
        }

        $duplicateGroups = DB::table('delivery_billing_records')
            ->select('delivery_id', 'billing_batch_id')
            ->selectRaw('COUNT(*) as record_count')
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isNotEmpty()) {
            $duplicates = $duplicateGroups->map(function (object $group): array {
                $recordsQuery = DB::table('delivery_billing_records')->where('delivery_id', $group->delivery_id);
                $group->billing_batch_id === null
                    ? $recordsQuery->whereNull('billing_batch_id')
                    : $recordsQuery->where('billing_batch_id', $group->billing_batch_id);

                return [
                    'delivery_id' => (int) $group->delivery_id,
                    'billing_batch_id' => $group->billing_batch_id === null ? null : (int) $group->billing_batch_id,
                    'billing_record_ids' => $recordsQuery->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                ];
            })->all();

            Log::warning('Skipped billing delivery/batch unique index because duplicate records require reviewed cleanup.', [
                'duplicates' => $duplicates,
            ]);

            return;
        }

        Schema::table('delivery_billing_records', function (Blueprint $table): void {
            $table->unique(['delivery_id', 'billing_batch_id'], 'delivery_billing_records_delivery_batch_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_billing_records')
            && Schema::hasIndex('delivery_billing_records', 'delivery_billing_records_delivery_batch_unique', 'unique')) {
            Schema::table('delivery_billing_records', function (Blueprint $table): void {
                $table->dropUnique('delivery_billing_records_delivery_batch_unique');
            });
        }
    }
};
