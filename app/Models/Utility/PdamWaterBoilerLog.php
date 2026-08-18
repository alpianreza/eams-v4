<?php

namespace App\Models\Utility;

class PdamWaterBoilerLog extends UtilityDailyLog
{
    protected $table = 'pdam_water_boiler_logs';

    public const VALUE_COLUMN = 'meter_reading';
    public const METER_STYLE = true;

    protected $fillable = ['log_date', 'log_time', 'meter_reading', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['meter_reading' => 'float'];
    }
}
