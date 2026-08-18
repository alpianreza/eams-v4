<?php

namespace App\Models\Thermal;

use Illuminate\Database\Eloquent\Model;

class ThermalImagingLocation extends Model
{
    protected $table = 'thermal_imaging_locations';

    protected $fillable = ['name', 'section', 'active', 'created_by'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
