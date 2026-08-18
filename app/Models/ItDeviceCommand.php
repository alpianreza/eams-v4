<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItDeviceCommand extends Model
{
    protected $table = 'it_device_commands';

    protected $fillable = [
        'device_id', 'command_id', 'command', 'payload_json', 'status',
        'result', 'requested_by', 'requested_at', 'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'requested_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ItDevice::class, 'device_id');
    }
}
