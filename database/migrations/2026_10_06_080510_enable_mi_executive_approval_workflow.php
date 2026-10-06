<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_requests', function (Blueprint $table): void {
            $table->enum('status', ['budget_requested', 'approved', 'released', 'in_progress', 'liquidated', 'closed', 'cancelled', 'rejected', 'returned_for_revision'])->default('budget_requested')->change();
        });
        Schema::table('liquidations', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'submitted', 'noted', 'approved', 'closed', 'rejected', 'returned_for_revision'])->default('draft')->change();
        });
        $permissions = collect(['mi.budget.approve', 'mi.travel.approve'])->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));
        Role::findOrCreate('executive', 'web')->givePermissionTo($permissions);
        Role::where('name', 'Executive')->where('guard_name', 'web')->first()?->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('Executive decisions and role grants require a reviewed forward reconciliation.');
    }
};
