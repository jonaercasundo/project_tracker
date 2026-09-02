<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users belonging to this company.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'company_user',
            'company_id',
            'user_id'
        )->withTimestamps();
    }
}