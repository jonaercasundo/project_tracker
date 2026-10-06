<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }
        DB::transaction(function (): void {
            $role = Role::where('name', 'MI Approver')->where('guard_name', 'web')->lockForUpdate()->first();
            if (! $role) {
                return;
            }
            if (Role::where('name', 'Executive')->where('guard_name', 'web')->exists()) {
                throw new RuntimeException('An Executive role already exists. Review its assignments and permissions before merging roles.');
            }
            $role->update(['name' => 'Executive']);
            DB::table('users')->where('role', 'MI Approver')->update(['role' => 'Executive']);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Role renames require a reviewed forward reconciliation.');
    }
};
