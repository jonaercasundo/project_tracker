<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Company;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'employee_id',
        'department',
        'position',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Companies this user belongs to.
     */
    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_user',
            'user_id',
            'company_id'
        )->withTimestamps();
    }

    /**
     * Check if user belongs to a company.
     */
    public function belongsToCompany($companyId): bool
    {
        return $this->companies()
            ->where('companies.company_id', $companyId)
            ->exists();
    }

    /**
     * Get the currently selected company.
     */
    public function currentCompany()
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return null;
        }

        return $this->companies()
            ->where('companies.company_id', $companyId)
            ->first();
    }

    /**
     * Get all users for role access management.
     */
    public function edit()
    {
        $users = User::all();

        return view('roleaccess.edit', compact('users'));
    }
}