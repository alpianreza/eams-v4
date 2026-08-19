<?php

namespace App\Support\Checklist;

use App\Models\Holiday;
use Illuminate\Support\Carbon;

/**
 * Unified Checklist Period Engine (Q-004: ONE centralized engine — never two).
 *
 * period_key (BR-01): daily `YYYY-MM-DD`, weekly `YYYY-MM-Wn`, monthly `YYYY-MM`.
 * Weekly uses month slices — W1=1–7, W2=8–14, W3=15–21, W4=22–end (NOT ISO week, BR-02).
 * Status (Q-004): DONE / OPEN / LATE / FUTURE / HOLIDAY.
 * Late is time-based (BR-03). Offday (BR-08): Sunday always; Saturday is a holiday
 * FROM 2026-04-01 (Q-005, configurable, NOT retroactive); plus the holidays table.
 */
class ChecklistPeriod
{
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MONTHLY = 'monthly';

    public const STATUS_DONE = 'DONE';
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_LATE = 'LATE';
    public const STATUS_FUTURE = 'FUTURE';
    public const STATUS_HOLIDAY = 'HOLIDAY';

    public static function periodKey(string $frequency, Carbon $date): string
    {
        return match ($frequency) {
            self::FREQ_DAILY => $date->format('Y-m-d'),
            self::FREQ_WEEKLY => $date->format('Y-m').'-W'.self::weekOfMonth($date),
            self::FREQ_MONTHLY => $date->format('Y-m'),
            default => throw new \InvalidArgumentException("Unknown frequency: {$frequency}"),
        };
    }

    /** Month-slice week number (BR-02): W1=1–7, W2=8–14, W3=15–21, W4=22–end. */
    public static function weekOfMonth(Carbon $date): int
    {
        $day = (int) $date->format('j');

        return match (true) {
            $day <= 7 => 1,
            $day <= 14 => 2,
            $day <= 21 => 3,
            default => 4,
        };
    }

    /** Time-based late threshold in days (BR-03). */
    public static function lateAfterDays(string $frequency): int
    {
        return match ($frequency) {
            self::FREQ_DAILY => 21,
            self::FREQ_WEEKLY => 28,
            self::FREQ_MONTHLY => 90, // ~3 months
            default => 0,
        };
    }

    /** Offday (BR-08): Sunday always; Saturday from effective date (Q-005); holidays table. */
    public static function isOffday(Carbon $date): bool
    {
        if ($date->isSunday()) {
            return true;
        }

        $saturdayEffective = Carbon::parse(config('eams.saturday_holiday_effective', '2026-04-01'))->startOfDay();
        if ($date->isSaturday() && $date->copy()->startOfDay()->gte($saturdayEffective)) {
            return true;
        }

        return Holiday::query()->where('holiday_date', $date->toDateString())->exists();
    }

    /**
     * Compute the canonical period status.
     *
     * @param  string  $frequency  daily|weekly|monthly
     * @param  Carbon  $date       a date inside the period
     * @param  bool  $hasResults   whether the period already has valid results
     * @param  Carbon|null  $now   injectable clock (for tests)
     */
    public static function status(string $frequency, Carbon $date, bool $hasResults, ?Carbon $now = null): string
    {
        $now = ($now ?? Carbon::now())->copy();

        // HOLIDAY applies to daily periods that fall on an offday (BR-08).
        if ($frequency === self::FREQ_DAILY && self::isOffday($date)) {
            return self::STATUS_HOLIDAY;
        }

        [$start, $end] = self::bounds($frequency, $date);

        // FUTURE: the period has not started yet (BR-04).
        if ($now->lt($start)) {
            return self::STATUS_FUTURE;
        }

        if ($hasResults) {
            return self::STATUS_DONE;
        }

        // LATE: now is past the period end + the time-based threshold (BR-03).
        $lateAt = $end->copy()->addDays(self::lateAfterDays($frequency));
        if ($now->gt($lateAt)) {
            return self::STATUS_LATE;
        }

        return self::STATUS_OPEN;
    }

    /**
     * Whether a period may be edited.
     *
     * BR-04 / Q-011 (decided 2026-08-19, keep legacy asymmetry):
     * - daily: editable unless future or an offday.
     * - weekly: editable while not future AND within a 3-month backfill grace window.
     * - monthly: always editable (unlimited backfill).
     */
    public static function isEditable(string $frequency, Carbon $date, ?Carbon $now = null): bool
    {
        $now = ($now ?? Carbon::now())->copy();

        if ($frequency === self::FREQ_DAILY && self::isOffday($date)) {
            return false;
        }

        [$start] = self::bounds($frequency, $date);

        // Future periods are never editable (BR-04).
        if ($now->lt($start)) {
            return false;
        }

        // Q-011: weekly backfill limited to a 3-month grace window; monthly is unlimited.
        if ($frequency === self::FREQ_WEEKLY) {
            return $now->lte($start->copy()->addMonths(3));
        }

        return true; // daily (non-offday, non-future) + monthly
    }

    /** [start, end] (Carbon pair) of the period containing $date. */
    public static function bounds(string $frequency, Carbon $date): array
    {
        return match ($frequency) {
            self::FREQ_DAILY => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            self::FREQ_WEEKLY => self::weekBounds($date),
            self::FREQ_MONTHLY => [$date->copy()->startOfMonth()->startOfDay(), $date->copy()->endOfMonth()->endOfDay()],
            default => throw new \InvalidArgumentException("Unknown frequency: {$frequency}"),
        };
    }

    protected static function weekBounds(Carbon $date): array
    {
        $monthStart = $date->copy()->startOfMonth();
        $lastDay = (int) $date->copy()->endOfMonth()->format('j');

        [$startDay, $endDay] = match (self::weekOfMonth($date)) {
            1 => [1, 7],
            2 => [8, 14],
            3 => [15, 21],
            default => [22, $lastDay],
        };

        return [
            $monthStart->copy()->day($startDay)->startOfDay(),
            $monthStart->copy()->day(min($endDay, $lastDay))->endOfDay(),
        ];
    }
}
