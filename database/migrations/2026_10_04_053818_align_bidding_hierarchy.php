<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table): void {
            $table->string('region_code', 10)->nullable();
            $table->string('province_code', 10)->nullable();
            $table->string('city_code', 10)->nullable();
            $table->string('barangay_code', 10)->nullable();
        });

        Schema::table('delivery_address', function (Blueprint $table): void {
            $table->text('delivery_address')->nullable();
            $table->string('region_code', 10)->nullable();
            $table->string('province_code', 10)->nullable();
            $table->string('city_code', 10)->nullable();
            $table->string('barangay_code', 10)->nullable();
        });

        Schema::table('keystages', function (Blueprint $table): void {
            $table->string('name')->nullable();
        });

        Schema::table('project_items', function (Blueprint $table): void {
            $table->foreignId('keystage_id')->nullable()->constrained('keystages')->nullOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('items')->nullOnDelete();
        });

        Schema::table('project_information', function (Blueprint $table): void {
            $table->decimal('calculated_total', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('keystage_id');
            $table->dropConstrainedForeignId('catalog_item_id');
        });

        Schema::table('project_information', function (Blueprint $table): void {
            $table->dropColumn('calculated_total');
        });

        Schema::table('keystages', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('delivery_address', function (Blueprint $table): void {
            $table->dropColumn(['delivery_address', 'region_code', 'province_code', 'city_code', 'barangay_code']);
        });

        Schema::table('lots', function (Blueprint $table): void {
            $table->dropColumn(['region_code', 'province_code', 'city_code', 'barangay_code']);
        });
    }
};
