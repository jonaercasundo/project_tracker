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
        Schema::create('pinterest_trends', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->index();
            $table->string('title')->nullable();
            $table->text('image')->nullable();
            $table->text('link')->nullable();
            $table->string('author')->nullable();
            $table->integer('score')->default(0);
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinterest_trends');
    }
};
