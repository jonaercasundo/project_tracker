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
        Schema::create('package_content_quantity_changes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('package_id')->index();
            $table->unsignedInteger('item_id')->index();
            $table->integer('old_qty')->nullable();
            $table->integer('new_qty');
            $table->text('remarks');
            $table->unsignedInteger('changed_by_user_id')->nullable()->index();
            $table->timestamp('changed_at')->useCurrent();
            $table->index(['package_id', 'item_id', 'changed_at'], 'package_content_qty_changes_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_content_quantity_changes');
    }
};
