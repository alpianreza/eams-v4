<?php

namespace Tests\Feature\ItDevice;

use App\Models\ItDevice;
use App\Models\ItDeviceCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_creates_device_and_returns_contract_shape(): void
    {
        $response = $this->postJson('/api/agent/heartbeat', [
            'hostname' => 'PC-NEW', 'mac' => 'AA:BB:CC:00:11:22', 'agent_version' => '1.0.0',
        ]);

        $response->assertOk()->assertJsonStructure([
            'status', 'device_token', 'heartbeat_interval', 'command_poll_interval', 'command', 'server_time',
        ]);

        $device = ItDevice::where('mac_address', 'AA:BB:CC:00:11:22')->firstOrFail();
        $this->assertSame('online', $device->status);
        $this->assertNotNull($device->last_seen);
        $this->assertNotNull($device->device_token);
    }

    public function test_heartbeat_is_idempotent_by_device_token(): void
    {
        $device = ItDevice::create(['hostname' => 'PC-1', 'device_token' => 'tok-abc', 'status' => 'offline']);

        $this->postJson('/api/agent/heartbeat', ['device_token' => 'tok-abc', 'hostname' => 'PC-1'])->assertOk();

        $this->assertSame(1, ItDevice::where('device_token', 'tok-abc')->count());
        $this->assertSame('online', $device->fresh()->status);
    }

    public function test_command_poll_pops_queued_command_and_marks_dispatched(): void
    {
        $device = ItDevice::create(['hostname' => 'PC-2', 'device_token' => 'tok-cmd']);
        $cmd = ItDeviceCommand::create([
            'device_id' => $device->id, 'command_id' => 'c1', 'command' => 'update',
            'status' => 'queued', 'requested_at' => now(),
        ]);

        $response = $this->postJson('/api/agent/command', ['device_token' => 'tok-cmd']);

        $response->assertOk()->assertJsonPath('command.name', 'update');
        $this->assertSame('dispatched', $cmd->fresh()->status);
    }

    public function test_command_poll_for_unknown_device_returns_missing(): void
    {
        $this->postJson('/api/agent/command', ['device_token' => 'nope'])
            ->assertOk()->assertJsonPath('status', 'missing')->assertJsonPath('command', null);
    }

    public function test_agent_update_for_unknown_device_returns_no_update(): void
    {
        $this->postJson('/api/agent/update', ['device_token' => 'nope'])
            ->assertOk()->assertJsonPath('update', false);
    }

    public function test_heartbeat_get_returns_alive_status(): void
    {
        $this->getJson('/api/agent/heartbeat')->assertOk()->assertJsonPath('status', 'ok');
    }
}
