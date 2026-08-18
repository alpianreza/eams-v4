<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginSession extends Model
{
    /** Uses started_at / last_seen_at / ended_at — no created_at/updated_at (matches production). */
    public $timestamps = false;

    protected $fillable = [
        'session_key', 'user_id', 'username', 'login_method', 'channel',
        'ip_address', 'user_agent', 'browser', 'platform', 'device_type',
        'started_at', 'last_seen_at', 'ended_at', 'last_route',
        'logout_reason', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
