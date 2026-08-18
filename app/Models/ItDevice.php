<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItDevice extends Model
{
    protected $table = 'it_devices';

    protected $fillable = [
        'asset_id', 'hostname', 'manufacturer', 'model', 'bios', 'device_user',
        'os', 'os_version', 'cpu_name', 'cpu_core', 'cpu_thread', 'gpu', 'disk_model',
        'architecture', 'ram_gb', 'storage_gb', 'last_ip', 'mac_address', 'agent_version',
        'last_update_check', 'last_seen', 'status', 'device_token', 'cpu',
    ];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'last_update_check' => 'datetime',
            'ram_gb' => 'integer',
            'storage_gb' => 'integer',
        ];
    }

    /**
     * Q-012: ONLINE when last_seen <= the centralized threshold (default 600s), else OFFLINE.
     * Single source: config('eams.device_online_threshold_seconds'). NOT a 48h threshold.
     */
    public function isOnline(): bool
    {
        if (empty($this->last_seen)) {
            return false;
        }

        $threshold = (int) config('eams.device_online_threshold_seconds', 600);

        return $this->last_seen->gte(now()->subSeconds($threshold));
    }

    public function commands(): HasMany
    {
        return $this->hasMany(ItDeviceCommand::class, 'device_id');
    }
}
