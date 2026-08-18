<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class ComplianceInventory extends Model
{
    public const STATUS_GOOD = 'good';
    public const STATUS_NEED_REPAIR = 'need_repair';
    public const STATUS_NOT_ACTIVE = 'not_active';

    /** Canonical inventory status (Q-017) — never mixed with checklist status. */
    public const STATUSES = [self::STATUS_GOOD, self::STATUS_NEED_REPAIR, self::STATUS_NOT_ACTIVE];

    protected $fillable = [
        'inventory_category_id', 'asset_item_type_id', 'area_id',
        'asset_code', 'type_description', 'specific_area',
        'status', 'remark', 'expired_date', 'qty', 'photo', 'qr_image', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'qty' => 'integer',
            // expired_date intentionally NOT date-cast: stored as a pure 'Y-m-d' string (portable).
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(AssetItemType::class, 'asset_item_type_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /** PIC source of truth (Q-007): max 2, equal, NO primary. */
    public function pics(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'compliance_inventory_pics')->withTimestamps();
    }

    /** Q-018: expiry is mainly for APAR; expired does NOT auto-mean NOT_ACTIVE (GOOD+EXPIRED is valid). */
    public function isExpired(): bool
    {
        if (empty($this->expired_date)) {
            return false;
        }

        return Carbon::parse($this->expired_date)->startOfDay()->isPast();
    }
}
