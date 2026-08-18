<?php

namespace App\Models\Utility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Base for the Boiler & Utility daily logs (BR-29/30): one row per calendar day,
 * monthly grid colored by offday (via the unified period engine), monthly total.
 * `log_date` is stored as a pure 'Y-m-d' string (portable) — parsed with Carbon when needed.
 */
abstract class UtilityDailyLog extends Model
{
    /** Numeric column summed/compared for the monthly total (set by subclass). */
    public const VALUE_COLUMN = 'value';

    /** When true the monthly total is `last - first` reading (meter), else `sum`. */
    public const METER_STYLE = false;

    protected $fillable = ['log_date', 'log_time', 'note', 'created_by'];

    public function scopeForMonth($query, string $ym)
    {
        return $query->where('log_date', 'like', $ym.'%');
    }

    /** Monthly total (BR-30): sum for consumables, last-minus-first for meter readings. */
    public static function monthlyTotal(string $ym): ?float
    {
        $rows = static::query()->forMonth($ym)->orderBy('log_date')->pluck(static::VALUE_COLUMN)->filter(fn ($v) => $v !== null);
        if ($rows->isEmpty()) {
            return null;
        }

        if (static::METER_STYLE) {
            return (float) ($rows->last() - $rows->first());
        }

        return (float) $rows->sum();
    }

    public function logDate(): Carbon
    {
        return Carbon::parse($this->log_date);
    }
}
