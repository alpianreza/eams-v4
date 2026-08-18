<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /** Append-only audit trail — created_at only, no updated_at (matches production). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'description', 'ip_address', 'user_agent',
        'session_id', 'status', 'login_method', 'channel', 'route',
        'request_method', 'device_type', 'browser', 'platform', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
