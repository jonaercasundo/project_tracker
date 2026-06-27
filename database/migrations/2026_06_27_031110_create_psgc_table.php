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
       Schema::create('psgc', function (Blueprint $table) {

        $table->id();

        $table->string('psgc_code',10)->unique();
        $table->string('correspondence_code',10)->nullable();

        $table->string('name');

        $table->enum('geographic_level',[
            'Reg',
            'Prov',
            'City',
            'Mun',
            'SubMun',
            'Bgy'
        ]);

        $table->string('parent_code',10)->nullable();

        $table->string('region_code',10)->nullable();

        $table->string('province_code',10)->nullable();

        $table->string('city_code',10)->nullable();

        $table->string('city_class')->nullable();

        $table->string('income_classification')->nullable();

        $table->char('urban_rural',1)->nullable();

        $table->unsignedInteger('population')->nullable();

        $table->string('status')->nullable();

            // Indexes
        $table->index('geographic_level');
        $table->index('region_code');
        $table->index('province_code');
        $table->index('city_code');

        $table->timestamps();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psgc');
    }
};
