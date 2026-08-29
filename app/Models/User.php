<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'fcm_token'];
    protected $hidden = ['password', 'remember_token', 'fcm_token'];
    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

    public function preference(): HasOne { return $this->hasOne(UserPreference::class); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
    public function projects(): HasMany { return $this->hasMany(Project::class); }
    public function locations(): HasMany { return $this->hasMany(Location::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function taskNotifications(): HasMany { return $this->hasMany(TaskNotification::class); }
}
