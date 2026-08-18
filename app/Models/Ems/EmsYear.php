<?php

namespace App\Models\Ems;

use Illuminate\Database\Eloquent\Model;

/** Base for EMS year metadata (production output for intensity + notes). */
abstract class EmsYear extends Model
{
    protected $fillable = ['report_year', 'production_output', 'notes'];

    protected function casts(): array
    {
        return ['report_year' => 'integer', 'production_output' => 'float'];
    }
}
