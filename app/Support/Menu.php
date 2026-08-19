<?php

namespace App\Support;

use App\Models\User;

/** Sidebar menu (BR-44): grouped catalog filtered by the user's page_access (admin sees all). */
class Menu
{
    /** Grouped menu items visible to the user. */
    public static function for(User $user): array
    {
        $groups = config('menu', []);

        return collect($groups)
            ->map(function (array $group) use ($user) {
                $group['items'] = collect($group['items'])
                    ->filter(fn (array $item) => self::visible($item, $user))
                    ->values()
                    ->all();

                return $group;
            })
            ->filter(fn (array $group) => count($group['items']) > 0)
            ->values()
            ->all();
    }

    protected static function visible(array $item, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $page = $item['page'] ?? null;
        if (! $page) {
            return true; // no page key → always visible
        }

        return $user->canAccessPage($page);
    }
}
