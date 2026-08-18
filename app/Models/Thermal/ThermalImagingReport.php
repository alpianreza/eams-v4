<?php

namespace App\Models\Thermal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThermalImagingReport extends Model
{
    protected $table = 'thermal_imaging_reports';

    // inspection_date stored as pure 'Y-m-d' string (portable) — no date-cast.
    protected $fillable = ['inspection_date', 'inspector_name', 'facility', 'area_name', 'created_by'];

    public function items(): HasMany
    {
        return $this->hasMany(ThermalImagingReportItem::class, 'report_id')->orderBy('sort_order');
    }
}
