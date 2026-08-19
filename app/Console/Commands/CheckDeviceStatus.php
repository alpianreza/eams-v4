<?php

namespace App\Console\Commands;

use App\Models\ItDevice;
use Illuminate\Console\Command;

/** Device status check (Q-012) — persist online/offline by the centralized threshold (run every minute). */
class CheckDeviceStatus extends Command
{
    protected $signature = 'eams:device-status-check';

    protected $description = 'Perbarui status online/offline perangkat berdasarkan threshold terpusat (Q-012).';

    public function handle(): int
    {
        $threshold = (int) config('eams.device_online_threshold_seconds', 600);
        $cutoff = now()->subSeconds($threshold);

        // Devices with no heartbeat within the threshold -> offline.
        $offline = ItDevice::where(fn ($q) => $q->whereNull('last_seen')->orWhere('last_seen', '<', $cutoff))
            ->where('status', '!=', 'offline')
            ->update(['status' => 'offline']);

        // Devices with a recent heartbeat -> online.
        $online = ItDevice::whereNotNull('last_seen')->where('last_seen', '>=', $cutoff)
            ->where('status', '!=', 'online')
            ->update(['status' => 'online']);

        $this->info("Device status diperbarui: {$online} online, {$offline} offline.");

        return self::SUCCESS;
    }
}
