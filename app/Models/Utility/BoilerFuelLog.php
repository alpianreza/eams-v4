<?php

namespace App\Models\Utility;

class BoilerFuelLog extends UtilityDailyLog
{
    protected $table = 'boiler_fuel_logs';

    public const VALUE_COLUMN = 'kg';
    public const METER_STYLE = false;

    protected $fillable = ['log_date', 'log_time', 'polybag', 'kg', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['polybag' => 'integer', 'kg' => 'float'];
    }
}
