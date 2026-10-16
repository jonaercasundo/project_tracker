<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Isolated test fixture for the production projects table, which predates Laravel migrations. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
            $table->string('project_code')->nullable();
            $table->string('ref_no', 50)->unique();
            $table->text('project_name');
            $table->string('agency');
            $table->decimal('contract_amount', 15, 2);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->nullable()->default('Pending');
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('keystage')->default(false);
            $table->float('ABC');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
