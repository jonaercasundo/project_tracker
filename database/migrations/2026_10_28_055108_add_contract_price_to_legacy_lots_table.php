<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lot', function (Blueprint $table): void {
            $table->decimal('contract_price', 15, 2)->nullable()->after('contract_no');
        });

        $projects = DB::table('lot')->select(['project_id', 'lot_id', 'lot_name'])->get()
            ->groupBy('project_id')
            ->map(function ($lots) {
                return $lots->map(function (object $lot): array {
                    $number = preg_replace('/^lot\s*/i', '', trim((string) $lot->lot_name));

                    return ['number' => $number, 'id' => $lot->lot_id];
                })->groupBy('number');
            })
            ->filter(fn ($lots): bool => collect(['2', '9', '11'])->every(fn (string $number): bool => $lots->has($number) && $lots->get($number)->count() === 1));

        if ($projects->count() !== 1) {
            return;
        }

        $lots = $projects->first();
        foreach (['2' => '141821425.55', '9' => '186633159.99', '11' => '229401493.02'] as $lotNumber => $price) {
            DB::table('lot')->where('lot_id', $lots->get($lotNumber)->first()['id'])->update(['contract_price' => $price]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot', function (Blueprint $table): void {
            $table->dropColumn('contract_price');
        });
    }
};
