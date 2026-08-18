<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function inventories(): HasMany
    {
        return $this->hasMany(ComplianceInventory::class, 'asset_item_type_id');
    }

    public function checklistQuestions(): HasMany
    {
        return $this->hasMany(ChecklistMaster::class, 'asset_item_type_id');
    }

    /**
     * Business identifier is `code`, never the auto-increment id (Q-015).
     */
    public static function findByCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }
}
