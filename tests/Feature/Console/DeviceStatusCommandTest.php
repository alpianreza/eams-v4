<?php

namespace Tests\Feature\Console;

use App\Models\ItDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_status_check_marks_online_and_offline(): void
    {
        $online = ItDevice::create(['hostname' => 'PC-01', 'device_token' => 'tok-1', 'last_seen' => now(), 'status' => 'offline']);
        $stale = ItDevice::create(['hostname' => 'PC-02', 'device_token' => 'tok-2', 'last_seen' => now()->subHour(), 'status' => 'online']);
        $never = ItDevice::create(['hostname' => 'PC-03', 'device_token' => 'tok-3', 'last_seen' => null, 'status' => 'online']);

        $this->artisan('eams:device-status-check')->assertSuccessful();

        $this->assertSame('online', $online->fresh()->status);
        $this->assertSame('offline', $stale->fresh()->status);
        $this->assertSame('offline', $never->fresh()->status);
    }
}
