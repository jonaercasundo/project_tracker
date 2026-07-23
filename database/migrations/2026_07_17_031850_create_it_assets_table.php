<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_assets', function (Blueprint $table) {
            $table->id();
            
            // Required Fields
            $table->string('asset_code')->unique();
            $table->string('qr_code')->unique();
            $table->string('asset_name');
            $table->string('category');
            $table->enum('status', ['Available', 'Assigned', 'Repair', 'Disposed'])->default('Available');
            
            // Optional Fields
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('specification')->nullable(); // Using text in case specs are long
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable(); // 10 digits total, 2 after decimal
            $table->date('warranty_expiry')->nullable();
            $table->string('assigned_to')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_assets');
    }
};