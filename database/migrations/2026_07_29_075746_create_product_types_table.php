<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_product_types', function (Blueprint $table) {

            $table->id();


            $table->foreignId('sub_category_id')
                  ->constrained('mi_sub_categories')
                  ->cascadeOnDelete();


            $table->string('code',10);

            $table->string('name');


            $table->text('description')
                  ->nullable();


            $table->boolean('is_active')
                  ->default(true);


            $table->timestamps();


            $table->unique([
                'sub_category_id',
                'code'
            ]);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('mi_product_types');
    }
};