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
        Schema::table('mi_materials', function (Blueprint $table): void {
            $table->string('normalized_name')->nullable()->unique();
        });

        $seenMaterials = [];
        foreach (DB::table('mi_materials')->orderBy('id')->get(['id', 'material_name']) as $material) {
            $normalized = mb_strtolower(trim($material->material_name));
            $value = isset($seenMaterials[$normalized]) ? null : $normalized;
            $seenMaterials[$normalized] = true;
            DB::table('mi_materials')->where('id', $material->id)->update(['normalized_name' => $value]);
        }

        Schema::create('mi_colors', function (Blueprint $table): void {
            $table->id();
            $table->string('color_name');
            $table->string('normalized_name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_colors');
        Schema::table('mi_materials', function (Blueprint $table): void {
            $table->dropUnique(['normalized_name']);
            $table->dropColumn('normalized_name');
        });
    }
};
