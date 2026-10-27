<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL may leave the table created if a later ALTER TABLE constraint fails.
     * The uploader id is optional audit metadata, so it does not depend on the
     * legacy users.user_id column type. The expense foreign key remains enforced.
     */
    public function up(): void
    {
        if (Schema::hasTable('mi_liquidation_item_receipts')) {
            return;
        }

        Schema::create('mi_liquidation_item_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liquidation_item_id')
                ->constrained('mi_liquidation_items')
                ->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_path', 500)->unique();
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('file_size');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->text('ocr_reference')->nullable();
            $table->timestamps();

            $table->index(['liquidation_item_id', 'id'], 'mi_liquidation_receipts_item_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_liquidation_item_receipts');
    }
};
