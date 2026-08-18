<?php

namespace App\Models\Thermal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThermalImagingReportItem extends Model
{
    protected $table = 'thermal_imaging_report_items';

    protected $fillable = ['report_id', 'location_id', 'location_name', 'celsius', 'thermal_image', 'findings', 'recommendation', 'sort_order'];

    protected function casts(): array
    {
        return ['celsius' => 'float'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ThermalImagingReport::class, 'report_id');
    }
}
