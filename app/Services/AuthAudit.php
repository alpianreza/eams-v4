<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Authentication audit (BR-40): every auth event is recorded to audit_logs
 * and the login session is tracked in login_sessions (idle-expiry support).
 */
class AuthAudit
{
    public static function loggedIn(Request $request, User $user): void
    {
        self::record($request, $user, 'login', 'success');
        self::startSession($request, $user);
    }

    public static function loggedOut(Request $request, ?User $user): void
    {
        self::record($request, $user, 'logout', 'success');
        self::endSession($request, 'logout');
    }

    public static function failed(Request $request, string $login): void
    {
        self::record($request, null, 'login', 'failed', "Failed login attempt for [{$login}]");
    }

    protected static function record(Request $request, ?User $user, string $action, string $status, ?string $description = null): void
    {
        $device = self::parseDevice($request);

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'session_id' => $request->hasSession() ? mb_substr($request->session()->getId(), 0, 64) : null,
            'status' => $status,
            'login_method' => 'password',
            'channel' => 'web',
            'route' => $request->path(),
            'request_method' => $request->method(),
            'device_type' => $device['device_type'],
            'browser' => $device['browser'],
            'platform' => $device['platform'],
        ]);
    }

    protected static function startSession(Request $request, User $user): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $device = self::parseDevice($request);

        LoginSession::create([
            'session_key' => mb_substr($request->session()->getId(), 0, 64),
            'user_id' => $user->id,
            'username' => $user->username,
            'login_method' => 'password',
            'channel' => 'web',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'browser' => $device['browser'],
            'platform' => $device['platform'],
            'device_type' => $device['device_type'],
            'started_at' => now(),
            'last_seen_at' => now(),
            'is_active' => true,
        ]);
    }

    protected static function endSession(Request $request, string $reason): void
    {
        if (! $request->hasSession()) {
            return;
        }

        LoginSession::query()
            ->where('session_key', mb_substr($request->session()->getId(), 0, 64))
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'logout_reason' => $reason,
            ]);
    }

    /** Naive user-agent parse (good enough for the audit context). */
    protected static function parseDevice(Request $request): array
    {
        $ua = strtolower((string) $request->userAgent());

        $platform = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Unknown',
        };

        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'chrome/') => 'Chrome',
            str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
            str_contains($ua, 'firefox/') => 'Firefox',
            default => 'Unknown',
        };

        $deviceType = match (true) {
            str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'mobile',
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'tablet',
            default => 'desktop',
        };

        return ['platform' => $platform, 'browser' => $browser, 'device_type' => $deviceType];
    }
}
