<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Notification fan-out. In-app (database) is the primary channel. Email/WhatsApp are
 * optional external channels driven by config — the WhatsApp (Fonnte) gateway needs an
 * API key (external config), so it is wired behind config and logged, not hard-coded.
 */
class NotificationService
{
    /** Create an in-app notification for a user. Returns the Notification. */
    public static function notify(User $user, string $title, ?string $body = null, ?string $url = null, string $type = 'info'): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
    }

    public static function unreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    }
}
