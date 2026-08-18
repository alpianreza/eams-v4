<?php

namespace App\Models\Patrol;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolLog extends Model
{
    protected $fillable = [
        'patrol_session_id', 'patrol_route_id', 'patrol_checkpoint_id', 'checked_by',
        'barcode_value', 'status', 'note', 'latitude', 'longitude', 'distance_m', 'photo_path', 'checked_at',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(PatrolCheckpoint::class, 'patrol_checkpoint_id');
    }
}
