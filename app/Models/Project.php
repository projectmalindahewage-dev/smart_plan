<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'schedule_date',
        'daily_goal',
        'daily_theme',
        'mood',
        'energy',
        'notes',
        'reflection',
        'planned_hours',
        'actual_hours',
        'productivity_score',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'planned_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'productivity_score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}