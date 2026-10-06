<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateMIApprover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mi:create-approver {email : New login email} {--name=MI Financial Approver : Display name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a dedicated MI budget and travel approver with a generated password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $values = ['email' => mb_strtolower(trim($this->argument('email'))), 'name' => trim($this->option('name'))];
        $validator = Validator::make($values, ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'name' => ['required', 'string', 'max:255']]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        if (! Company::where('code', 'MI')->where('is_active', true)->exists()) {
            $this->error('An active MI company is required.');

            return self::FAILURE;
        }
        $password = Str::password(20, spaces: false);
        DB::transaction(function () use ($values, $password): void {
            $company = Company::where('code', 'MI')->where('is_active', true)->lockForUpdate()->firstOrFail();
            $user = User::create($values + ['username' => $values['email'], 'role' => 'Executive', 'password' => $password]);
            $user->assignRole(Role::findOrCreate('Executive', 'web'));
            $user->companies()->attach($company->getKey());
            foreach (['mi.budget.approve', 'mi.travel.approve'] as $permission) {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        });
        $this->info('MI approver created.');
        $this->line('Email: '.$values['email']);
        $this->line('Temporary password: '.$password);

        return self::SUCCESS;
    }
}
