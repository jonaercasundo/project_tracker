<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_history', function (Blueprint $table) {

            $table->id();

            $table->integer('package_status_id');

            $table->integer('user_id');

            $table->string('status');

            $table->text('remarks')->nullable();

            $table->decimal('latitude',10,7)->nullable();

            $table->decimal('longitude',10,7)->nullable();

            $table->integer('accuracy')->nullable();

            $table->decimal('distance_from_school',8,2)->nullable();

            $table->timestamps();

            $table->foreign('package_status_id')
                ->references('package_status_id')
                ->on('package_status')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_history');
    }
};