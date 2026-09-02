<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            // Must match users.user_id exactly: int(11)
            $table->integer('user_id');

            // Matches companies.company_id: bigint(20) unsigned
            $table->unsignedBigInteger('company_id');

            $table->timestamps();

            $table->primary(['user_id', 'company_id']);

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('company_id')
                ->references('company_id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};