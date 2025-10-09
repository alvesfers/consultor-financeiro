<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CONSULTANT = 'consultant';

    public const ROLE_CLIENT = 'client';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'timezone', 'locale', 'active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
    ];

    // ---- Relations
    public function consultant()
    {
        return $this->hasOne(Consultant::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // ---- Helpers
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isConsultant(): bool
    {
        return $this->role === self::ROLE_CONSULTANT;
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }
}
