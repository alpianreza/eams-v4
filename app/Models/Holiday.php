<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['holiday_date', 'description'];

    /*
     * holiday_date is a DATE column kept as a pure 'Y-m-d' string (no datetime cast),
     * so the `unique` validation rule and the DB constraint agree on the format on both
     * SQLite (tests) and MariaDB (production). Parse with Carbon when a date object is needed.
     */
}
