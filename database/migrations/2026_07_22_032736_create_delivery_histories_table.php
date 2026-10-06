<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_status') || ! Schema::hasColumn('package_status', 'package_status_id')) {
            throw new RuntimeException('Restore the legacy package_status table with its package_status_id primary key before migrating delivery history.');
        }

        Schema::create('delivery_history', function (Blueprint $table) {
            $table->id();

            // Replaces both the integer() and foreign() methods for package_status
            $table->foreignId('package_status_id')
                ->constrained('package_status', 'package_status_id')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            $table->string('status');
            $table->text('remarks')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('accuracy')->nullable();
            $table->decimal('distance_from_school', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_history');
    }
};
