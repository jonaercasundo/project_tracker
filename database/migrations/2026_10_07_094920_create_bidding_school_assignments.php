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
        Schema::table('project_information', function (Blueprint $table): void {
            $table->unsignedInteger('school_revision')->default(0);
        });
        Schema::create('bidding_packages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('key')->unique();
            $table->foreignId('keystage_id')->constrained('keystages')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('grade_alias', 100);
            $table->string('quantity_basis', 20);
            $table->unsignedInteger('cartons')->default(0);
            $table->decimal('cbm', 15, 4)->default(0);
            $table->decimal('volumetric_weight', 15, 4)->default(0);
            $table->decimal('gross_weight', 15, 4)->default(0);
            $table->timestamps();
            $table->unique(['keystage_id', 'code']);
        });
        Schema::create('bidding_school_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained('bidding_packages')->restrictOnDelete();
            $school = $table->string('school_id');
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $school->collation('utf8mb4_general_ci');
            }
            $table->foreign('school_id')->references('school_id')->on('school')->restrictOnDelete();
            $table->integer('delivery_id')->nullable();
            $table->foreign('delivery_id')->references('delivery_id')->on('deliveries')->restrictOnDelete();
            $table->unique(['package_id', 'school_id'], 'bidding_assignment_identity');
            $table->timestamps();
        });
        Schema::table('project_items', function (Blueprint $table): void {
            $table->foreignId('bidding_package_id')->nullable()->constrained('bidding_packages')->restrictOnDelete();
        });
        Schema::create('bidding_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('project_information')->cascadeOnDelete();
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->string('filename');
            $table->unsignedInteger('rows_processed');
            $table->unsignedInteger('valid_assignments');
            $table->unsignedInteger('rejected_rows');
            $table->json('summary');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bidding_imports');
        Schema::table('project_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bidding_package_id');
        });
        Schema::dropIfExists('bidding_school_assignments');
        Schema::dropIfExists('bidding_packages');
        Schema::table('project_information', function (Blueprint $table): void {
            $table->dropColumn('school_revision');
        });
    }
};
