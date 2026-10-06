<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateAdministrator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-administrator {--name= : Administrator name} {--email= : Login email} {--company=* : Active company code (MI or MMC); may be repeated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an administrator with a privately entered password and active company membership';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('companies') || ! Schema::hasTable('company_user')) {
            $this->error('The companies and company_user tables must be installed before creating a login account.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively to enter your password privately.');

            return self::FAILURE;
        }

        $companies = Company::where('is_active', true)->whereIn('code', ['MI', 'MMC'])->orderBy('code')->get();
        if ($companies->isEmpty()) {
            $this->error('An active MI or MMC company must exist before creating a login account.');

            return self::FAILURE;
        }

        $companyCodes = $this->option('company');
        if ($companyCodes === []) {
            $companyCodes = $this->choice('Company (comma-separated choices for multiple companies)', $companies->pluck('code')->all(), null, null, true);
        }
        $values = [
            'name' => trim((string) ($this->option('name') ?? $this->ask('Administrator name'))),
            'email' => mb_strtolower(trim((string) ($this->option('email') ?? $this->ask('Login email')))),
            'companies' => $companyCodes,
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'companies' => ['required', 'array', 'min:1'],
            'companies.*' => ['required', 'string', 'distinct', Rule::in($companies->pluck('code')->all())],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($values): void {
            $companies = Company::whereIn('code', $values['companies'])->where('is_active', true)->lockForUpdate()->get();
            if ($companies->count() !== count($values['companies'])) {
                throw ValidationException::withMessages(['companies' => 'The selected company is no longer active.']);
            }
            $role = Role::findOrCreate('Administrator', 'web');
            $user = User::create([
                'name' => $values['name'],
                'email' => $values['email'],
                'username' => $values['email'],
                'role' => 'admin',
                'password' => $values['password'],
            ]);
            $user->assignRole($role);
            $user->companies()->attach($companies->modelKeys());
        });

        $this->info('Administrator created. Sign in with the email and password you entered.');

        return self::SUCCESS;
    }
}
