<?php

namespace App\Models\Fdm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FdmProductionSectionYear extends Model
{
    protected $table = 'fdm_production_section_years';

    protected $fillable = ['report_year'];

    protected function casts(): array
    {
        return ['report_year' => 'integer'];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FdmProductionSectionEntry::class, 'year_id')->orderBy('display_order');
    }
}
