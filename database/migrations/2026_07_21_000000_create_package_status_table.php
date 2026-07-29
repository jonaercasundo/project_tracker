<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_status', function (Blueprint $table) {
            $table->id(); // This creates the primary key that your foreign key looks for
            $table->string('name'); // e.g., 'Delivered', 'In Transit'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_status');
    }
};