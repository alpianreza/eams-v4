<?php

namespace App\Models\Patrol;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolSession extends Model
{
    protected $fillable = [
        'patrol_route_id', 'patrol_date', 'started_by', 'started_at', 'ended_at',
        'status', 'total_checkpoints', 'checked_count', 'issue_count',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(PatrolRoute::class, 'patrol_route_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PatrolLog::class, 'patrol_session_id');
    }

    public function isComplete(): bool
    {
        return $this->total_checkpoints > 0 && $this->checked_count >= $this->total_checkpoints;
    }
}
