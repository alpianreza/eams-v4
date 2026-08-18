<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['holiday_date', 'description'];

    protected function casts(): array
    {
        return ['holiday_date' => 'date'];
    }
}
