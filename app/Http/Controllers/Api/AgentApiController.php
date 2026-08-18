<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItDevice;
use App\Models\ItDeviceCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Agent API (`/api/agent/*`) — contract-compatible with the EAMS legacy agent (BR-35/36).
 * Auth is via `device_token` (machine client — NOT web session). Endpoints are CSRF-exempt
 * (api routes). Response shape preserved: status, device_token, heartbeat_interval,
 * command_poll_interval, command, server_time.
 *
 * NOTE: advanced legacy agent features (update channels win7/xp, remote lock, hardware
 * deep-normalization) are intentionally deferred as technical scope — not business rules.
 */
class AgentApiController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $this->payload($request);

        if (empty($data)) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Agent API aktif',
                'server_time' => now()->toAtomString(),
            ]);
        }

        $device = $this->findDevice($data);
        $token = trim((string) ($data['device_token'] ?? '')) ?: ($device?->device_token ?? bin2hex(random_bytes(16)));

        $payload = [
            'hostname' => $data['hostname'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
            'model' => $data['model'] ?? null,
            'os' => $data['os'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'cpu_name' => $data['cpu_name'] ?? null,
            'ram_gb' => $data['ram_gb'] ?? null,
            'storage_gb' => $data['storage_gb'] ?? null,
            'last_ip' => trim((string) ($data['lan_ip'] ?? '')) ?: $request->ip(),
            'mac_address' => $data['mac'] ?? null,
            'agent_version' => $data['agent_version'] ?? null,
            'last_update_check' => now(),
            'last_seen' => now(),
            'status' => 'online',
            'device_token' => $token,
            'cpu' => json_encode($data['diagnostics'] ?? [], JSON_UNESCAPED_UNICODE),
        ];

        $device = $device ? tap($device)->update($payload) : ItDevice::create($payload + ['hostname' => $data['hostname'] ?? 'unknown']);

        $command = $this->popQueuedCommand($device);

        return response()->json([
            'status' => 'ok',
            'device_token' => $token,
            'heartbeat_interval' => (int) config('eams.agent_heartbeat_interval_seconds', 300),
            'command_poll_interval' => (int) config('eams.agent_command_poll_interval_seconds', 30),
            'command' => $command,
            'server_time' => now()->toAtomString(),
        ]);
    }

    public function command(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $device = $this->findDevice($data);

        if (! $device) {
            return response()->json([
                'status' => 'missing',
                'heartbeat_interval' => (int) config('eams.agent_heartbeat_interval_seconds', 300),
                'command_poll_interval' => (int) config('eams.agent_command_poll_interval_seconds', 30),
                'command' => null,
                'server_time' => now()->toAtomString(),
            ]);
        }

        $device->update(['last_seen' => now(), 'status' => 'online']);

        return response()->json([
            'status' => 'ok',
            'device_token' => $device->device_token,
            'heartbeat_interval' => (int) config('eams.agent_heartbeat_interval_seconds', 300),
            'command_poll_interval' => (int) config('eams.agent_command_poll_interval_seconds', 30),
            'command' => $this->popQueuedCommand($device),
            'server_time' => now()->toAtomString(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $device = $this->findDevice($data);

        // Unknown device → no update (legacy-compatible).
        return response()->json(['update' => false]);
    }

    /** Queue a remote command for a device (used by the admin UI). */
    public function queueCommand(ItDevice $device, string $command, array $args = [], ?string $requestedBy = null): ItDeviceCommand
    {
        return ItDeviceCommand::create([
            'device_id' => $device->id,
            'command_id' => (string) Str::random(24),
            'command' => $command,
            'payload_json' => $args ?: null,
            'status' => 'queued',
            'requested_by' => $requestedBy,
            'requested_at' => now(),
        ]);
    }

    /** Pop the oldest queued command and mark it dispatched (legacy popQueuedCommand). */
    protected function popQueuedCommand(ItDevice $device): ?array
    {
        $cmd = ItDeviceCommand::where('device_id', $device->id)
            ->where('status', 'queued')
            ->orderBy('requested_at')
            ->first();

        if (! $cmd) {
            return null;
        }

        $cmd->update(['status' => 'dispatched']);

        return [
            'id' => $cmd->command_id,
            'name' => $cmd->command,
            'args' => $cmd->payload_json ?? [],
            'queued_at' => optional($cmd->requested_at)->toAtomString(),
        ];
    }

    protected function findDevice(array $data): ?ItDevice
    {
        $token = trim((string) ($data['device_token'] ?? ''));
        $mac = trim((string) ($data['mac'] ?? ''));
        $hostname = trim((string) ($data['hostname'] ?? ''));

        if ($token !== '') {
            $found = ItDevice::where('device_token', $token)->first();
            if ($found) {
                return $found;
            }
        }
        if ($mac !== '') {
            $found = ItDevice::where('mac_address', $mac)->first();
            if ($found) {
                return $found;
            }
        }
        if ($hostname !== '') {
            return ItDevice::where('hostname', $hostname)->orderByDesc('last_seen')->first();
        }

        return null;
    }

    protected function payload(Request $request): array
    {
        $json = $request->json()->all();
        if (is_array($json) && $json !== []) {
            return $json;
        }

        $all = $request->all();

        return is_array($all) ? $all : [];
    }
}
