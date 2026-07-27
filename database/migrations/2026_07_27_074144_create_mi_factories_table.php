<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_factories', function (Blueprint $table) {
            $table->id('factory_id');

            $table->string('factory_name');
            $table->string('country')->nullable();

            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->text('address')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique('factory_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_factories');
    }
};