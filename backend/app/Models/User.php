<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'creator_id');
    }

    public function validatedTransactions()
    {
        return $this->hasMany(Transaction::class, 'validator_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin_cssd';
    }

    public function isPetugasCSSD()
    {
        return $this->role === 'petugas_cssd';
    }

    public function isPerawatUnit()
    {
        return $this->role === 'perawat_unit';
    }

    public function isSupervisor()
    {
        return $this->role === 'supervisor';
    }

    public function canCreateTransactions()
    {
        return in_array($this->role, ['admin_cssd', 'petugas_cssd']);
    }

    public function canValidateTransactions()
    {
        return in_array($this->role, ['admin_cssd', 'perawat_unit', 'supervisor']);
    }

    public function canManageUsers()
    {
        return $this->role === 'admin_cssd';
    }
}
