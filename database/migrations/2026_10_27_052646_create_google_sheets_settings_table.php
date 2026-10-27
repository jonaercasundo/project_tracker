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
        Schema::create('google_sheets_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('spreadsheet_id', 200);
            $table->string('sheet_tab', 100)->default('Copy of SME LOT 2,9,11');
            $table->text('service_account_json')->nullable();
            $table->boolean('enabled')->default(false);
            $table->unsignedBigInteger('configured_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_sheets_settings');
    }
};
