<?php

namespace App\Support\Ui;

use Illuminate\Support\Str;

/**
 * Satu sumber presentasi status di frontend.
 *
 * Kelas ini hanya memetakan label, tone, dan ikon. Keputusan/status domain tetap
 * dihitung oleh service dan rule yang sudah ada.
 */
final class StatusPresentation
{
    private const MAP = [
        // Checklist
        'OK' => ['label' => 'OK', 'tone' => 'success', 'icon' => 'check-circle-fill'],
        'NOT_OK' => ['label' => 'Not OK', 'tone' => 'danger', 'icon' => 'x-circle-fill'],
        'NA' => ['label' => 'N/A', 'tone' => 'neutral', 'icon' => 'dash-circle'],

        // Canonical period engine
        'DONE' => ['label' => 'Done', 'tone' => 'success', 'icon' => 'check-circle-fill'],
        'OPEN' => ['label' => 'Open', 'tone' => 'info', 'icon' => 'clock-fill'],
        'LATE' => ['label' => 'Late', 'tone' => 'danger', 'icon' => 'exclamation-circle-fill'],
        'FUTURE' => ['label' => 'Future', 'tone' => 'neutral', 'icon' => 'calendar-event'],
        'HOLIDAY' => ['label' => 'Holiday', 'tone' => 'neutral', 'icon' => 'calendar2-week'],

        // Inventory
        'GOOD' => ['label' => 'Good', 'tone' => 'success', 'icon' => 'check-circle-fill'],
        'NEED_REPAIR' => ['label' => 'Need Repair', 'tone' => 'warning', 'icon' => 'tools'],
        'NOT_ACTIVE' => ['label' => 'Not Active', 'tone' => 'neutral', 'icon' => 'pause-circle-fill'],

        // APAR expiry presentation (tidak mengubah status Inventory)
        'VALID' => ['label' => 'Valid', 'tone' => 'success', 'icon' => 'shield-check'],
        'NEAR_EXPIRY' => ['label' => 'Near Expiry', 'tone' => 'warning', 'icon' => 'hourglass-split'],
        'EXPIRED' => ['label' => 'Expired', 'tone' => 'danger', 'icon' => 'exclamation-octagon-fill'],

        // Device monitoring
        'ONLINE' => ['label' => 'Online', 'tone' => 'success', 'icon' => 'wifi'],
        'OFFLINE' => ['label' => 'Offline', 'tone' => 'danger', 'icon' => 'wifi-off'],
    ];

    /** @return array{key: string, label: string, tone: string, icon: string} */
    public static function for(string $status): array
    {
        $key = Str::of($status)->trim()->upper()->replace([' ', '-'], '_')->toString();
        $fallbackLabel = Str::of($key)->replace('_', ' ')->lower()->title()->toString();
        $presentation = self::MAP[$key] ?? [
            'label' => $fallbackLabel !== '' ? $fallbackLabel : 'Unknown',
            'tone' => 'neutral',
            'icon' => 'circle-fill',
        ];

        return ['key' => $key, ...$presentation];
    }

    /** @return list<string> */
    public static function canonicalKeys(): array
    {
        return array_keys(self::MAP);
    }
}
