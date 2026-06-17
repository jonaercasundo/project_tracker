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
        Schema::create('tiktok_trends', function (Blueprint $table) {
            $table->id();
            $table->string('tiktok_id')->unique();
            $table->string('hashtag');
            $table->text('description')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_nickname')->nullable();
            $table->string('author_id')->nullable();
            $table->string('author_url')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedBigInteger('fans')->default(0);
            $table->string('cover_url')->nullable();
            $table->string('post_url')->nullable();
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedInteger('duration')->default(0);
            $table->string('region')->nullable();
            $table->json('hashtags')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_trends');
    }
};
