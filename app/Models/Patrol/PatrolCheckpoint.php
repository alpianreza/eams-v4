<?php

namespace App\Models\Patrol;

use Illuminate\Database\Eloquent\Model;

class PatrolCheckpoint extends Model
{
    protected $fillable = ['code', 'name', 'area', 'barcode_value', 'lat', 'lng', 'radius_m', 'map_x', 'map_y', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'lat' => 'float', 'lng' => 'float', 'radius_m' => 'integer'];
    }

    /** Haversine distance in meters from a given coordinate to this checkpoint. */
    public function distanceFrom(?float $lat, ?float $lng): ?float
    {
        if ($lat === null || $lng === null || $this->lat === null || $this->lng === null) {
            return null;
        }

        $earth = 6371000.0;
        $dLat = deg2rad($lat - (float) $this->lat);
        $dLng = deg2rad($lng - (float) $this->lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad((float) $this->lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
