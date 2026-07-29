<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('mi_collections', function (Blueprint $table) {


            $table->id();


            $table->foreignId('product_type_id')
                  ->constrained('mi_product_types')
                  ->cascadeOnDelete();


            $table->string('code',10);


            $table->string('name');


            $table->text('description')
                  ->nullable();


            $table->boolean('is_active')
                  ->default(true);


            $table->timestamps();


            $table->unique([
                'product_type_id',
                'code'
            ]);

        });
    }



    public function down(): void
    {
        Schema::dropIfExists('mi_collections');
    }
};