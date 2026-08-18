<?php

namespace App\Models\Fdm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FdmProductionSectionEntry extends Model
{
    protected $table = 'fdm_production_section_entries';

    protected $fillable = ['year_id', 'section_key', 'section_label', 'entry_type', 'frequency_label', 'logo_path', 'display_order', 'monthly_values'];

    protected function casts(): array
    {
        return ['monthly_values' => 'array', 'display_order' => 'integer'];
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(FdmProductionSectionYear::class, 'year_id');
    }

    /** Sum of the 12 monthly values. */
    public function yearlyTotal(): float
    {
        return (float) collect($this->monthly_values ?? [])->sum(fn ($v) => (float) $v);
    }
}
