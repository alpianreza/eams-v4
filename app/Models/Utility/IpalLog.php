<?php

namespace App\Models\Utility;

class IpalLog extends UtilityDailyLog
{
    protected $table = 'ipal_logs';

    public const VALUE_COLUMN = 'value';
    public const METER_STYLE = false;

    protected $fillable = ['log_date', 'log_time', 'value', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['value' => 'float'];
    }
}
