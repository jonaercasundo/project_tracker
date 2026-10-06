<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class GrantMIAccountingAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mi:accounting-access {email : Existing Accounting account} {--read-only : Grant the six read-only workspace capabilities} {--permission=* : Explicit additional Accounting capability}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explicitly provision MI Accounting capabilities without granting approval or changing company membership';

    public const READ_ONLY = ['mi.accounting.dashboard.view', 'mi.budget.view', 'mi.liquidation.view', 'mi.settlement.view', 'mi.financial-reports.view', 'mi.audit.view'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $permissions = array_values(array_unique(array_merge($this->option('read-only') ? self::READ_ONLY : [], $this->option('permission'))));
        $allowed = array_merge(self::READ_ONLY, ['mi.budget.review', 'mi.budget.release', 'mi.liquidation.review']);
        if ($permissions === [] || array_diff($permissions, $allowed)) {
            $this->error('Choose --read-only or explicit supported Accounting permissions. Approval, settlement processing and closure grants require separate confirmed provisioning.');

            return self::FAILURE;
        }
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user || ! $user->hasRole('accounting') || ! $user->companies()->where('code', 'MI')->where('companies.is_active', true)->exists()) {
            $this->error('An existing Accounting user with active MI membership is required.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user, $permissions): void {
            foreach ($permissions as $permission) {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        });
        $this->info('Granted: '.implode(', ', $permissions));
        $this->info('Existing company memberships, roles, and other permissions were preserved.');

        return self::SUCCESS;
    }
}
