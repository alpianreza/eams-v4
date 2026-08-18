<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $table = 'compliance_calendar_events';

    protected $fillable = ['title', 'start_at', 'end_at', 'all_day', 'color', 'sticker', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'end_at' => 'datetime', 'all_day' => 'boolean'];
    }
}
