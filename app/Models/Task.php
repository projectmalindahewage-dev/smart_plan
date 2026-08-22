<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'description', 'category', 'priority', 'date',
        'start_time', 'end_time', 'latitude', 'longitude', 'status', 'enabled',
        'completion_percentage', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'enabled' => 'boolean',
        'completion_percentage' => 'integer',
        'weather_data' => 'array',
        'weather_fetched_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subtasks(): HasMany { return $this->hasMany(SubTask::class); }
}