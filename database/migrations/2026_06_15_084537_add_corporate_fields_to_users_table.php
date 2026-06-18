<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->unique()->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('employee_id');
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('department');
            }
        });

        // Modify existing role enum to add new values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'Super Admin',
            'Warehouse Admin',
            'Warehouse Coordinator',
            'Office Admin',
            'Office Coordinator',
            'Viewer',
            'Administrator',
            'user',
            'manager',
            'finance'
        ) NULL");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'employee_id')) {
                $table->dropColumn('employee_id');
            }
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
        });

        // Revert role enum to original values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'Super Admin',
            'Warehouse Admin',
            'Warehouse Coordinator',
            'Office Admin',
            'Office Coordinator',
            'Viewer'
        ) NULL");
    }
};