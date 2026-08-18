<?php

namespace App\Models\Patrol;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PatrolRoute extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** Checkpoints in patrol order. */
    public function checkpoints(): BelongsToMany
    {
        return $this->belongsToMany(PatrolCheckpoint::class, 'patrol_route_checkpoints')
            ->withPivot('route_order')
            ->withTimestamps()
            ->orderBy('patrol_route_checkpoints.route_order');
    }
}
