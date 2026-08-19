<?php

namespace App\Models\ItAsset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = ['inventory_no', 'category_id', 'asset_name', 'brand', 'serial_number', 'photo', 'status', 'location', 'purchase_date'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id');
    }

    /** The current (unreturned) assignment. */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class, 'asset_id')->whereNull('returned_at')->latest('assigned_at');
    }
}
