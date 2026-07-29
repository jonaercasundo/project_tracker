<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_sub_categories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                  ->constrained('mi_categories')
                  ->cascadeOnDelete();

            $table->string('code',10);

            $table->string('name');

            $table->text('description')
                  ->nullable();

            $table->boolean('is_active')
                  ->default(true);

            $table->timestamps();


            $table->unique([
                'category_id',
                'code'
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('mi_sub_categories');
    }
};