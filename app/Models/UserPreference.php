<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wake_time',
        'sleep_time',
        'working_start',
        'working_end',
        'productivity_pattern',
        'default_task_duration',
        'week_start',
        'notification_enabled',
        'theme',
    ];

    protected $casts = [
        'notification_enabled' => 'boolean',
        'default_task_duration' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}