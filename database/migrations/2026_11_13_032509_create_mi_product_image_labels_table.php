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
        Schema::create('mi_product_image_labels', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 255)->unique();
            $table->timestamps();
        });

        $labels = collect(['Plate', 'Render', 'CAD Drawing', 'Actual Photo']);

        if (Schema::hasColumn('mi_product_images', 'label')) {
            $labels = $labels->merge(DB::table('mi_product_images')
                ->whereNotNull('label')
                ->whereRaw("TRIM(label) <> ''")
                ->distinct()
                ->pluck('label'));
        }

        $now = now();
        DB::table('mi_product_image_labels')->insertOrIgnore(
            $labels
                ->map(static fn ($label): string => trim((string) $label))
                ->filter()
                ->unique(fn (string $label): string => mb_strtolower($label))
                ->map(static fn (string $label): array => [
                    'label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_product_image_labels');
    }
};
