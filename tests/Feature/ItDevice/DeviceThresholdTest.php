<?php

namespace Tests\Feature\ItDevice;

use App\Models\ItDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeviceThresholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_is_online_when_last_seen_within_threshold(): void
    {
        Carbon::setTestNow('2026-08-18 12:00:00');
        $device = ItDevice::create(['hostname' => 'PC-01', 'device_token' => 'tok1', 'last_seen' => now()->subMinutes(5), 'status' => 'online']);

        // Q-012: within 10 minutes → ONLINE
        $this->assertTrue($device->isOnline());
    }

    public function test_device_is_offline_when_last_seen_exceeds_threshold(): void
    {
        Carbon::setTestNow('2026-08-18 12:00:00');
        $device = ItDevice::create(['hostname' => 'PC-02', 'device_token' => 'tok2', 'last_seen' => now()->subMinutes(11), 'status' => 'online']);

        // Q-012: > 10 minutes → OFFLINE (NOT a 48h rule)
        $this->assertFalse($device->isOnline());
    }

    public function test_threshold_is_read_from_centralized_config(): void
    {
        Carbon::setTestNow('2026-08-18 12:00:00');
        config()->set('eams.device_online_threshold_seconds', 120);
        $device = ItDevice::create(['hostname' => 'PC-03', 'device_token' => 'tok3', 'last_seen' => now()->subMinutes(5)]);

        // 5 min ago > 120s threshold → offline (proves it reads config, not hard-coded)
        $this->assertFalse($device->isOnline());
    }

    public function test_device_with_no_last_seen_is_offline(): void
    {
        $device = ItDevice::create(['hostname' => 'PC-04', 'device_token' => 'tok4']);
        $this->assertFalse($device->isOnline());
    }
}
