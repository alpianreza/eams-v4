<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetItemType extends Model
{
    protected $fillable = [
        'inventory_category_id', 'name', 'code',
        'checklist_frequency', 'allow_na', 'active',
    ];

    protected function casts(): array
    {
        return [
            'allow_na' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    /**
     * Business identifier is `code`, never the auto-increment id (Q-015).
     * Behavior must resolve an item type by its stable code (APAR, CCTV, ...).
     */
    public static function findByCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }
}
