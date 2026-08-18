<?php

namespace App\Models\Ems;

use Illuminate\Database\Eloquent\Model;

/** Base for EMS monthly per-section consumption entries (unique per year+section+month). */
abstract class EmsEntry extends Model
{
    protected $fillable = ['report_year', 'section_key', 'report_month', 'consumption_amount'];

    protected function casts(): array
    {
        return ['report_year' => 'integer', 'report_month' => 'integer', 'consumption_amount' => 'float'];
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('report_year', $year);
    }

    /** [section_key][report_month] => consumption_amount (for the year matrix). */
    public static function matrixForYear(int $year): array
    {
        $matrix = [];
        foreach (static::forYear($year)->get() as $e) {
            $matrix[$e->section_key][$e->report_month] = $e->consumption_amount;
        }

        return $matrix;
    }
}
