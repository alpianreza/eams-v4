<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistLog extends Model
{
    public const STATUS_OK = 'ok';
    public const STATUS_NOT_OK = 'not_ok';
    public const STATUS_NA = 'na';
    public const STATUSES = [self::STATUS_OK, self::STATUS_NOT_OK, self::STATUS_NA];

    protected $fillable = [
        'inventory_id', 'asset_item_type_id', 'checklist_master_id',
        'check_date', 'period_key', 'time_slot', 'status', 'remark', 'photo',
        'checked_by_user_id', 'checked_by_name', 'mode',
        'follow_up_status', 'follow_up_note', 'follow_up_date',
    ];

    protected function casts(): array
    {
        return ['check_date' => 'date:Y-m-d', 'follow_up_date' => 'date:Y-m-d'];
    }

    /** Q-023: write an audit-trail row whenever a log is corrected (update). */
    protected static function booted(): void
    {
        static::updating(function (ChecklistLog $log): void {
            $log->histories()->create([
                'changed_by_user_id' => $log->checked_by_user_id,
                'changed_by_name' => $log->checked_by_name,
                'old_status' => $log->getOriginal('status'),
                'new_status' => $log->status,
                'old_remark' => $log->getOriginal('remark'),
                'new_remark' => $log->remark,
                'old_photo' => $log->getOriginal('photo'),
                'new_photo' => $log->photo,
                'changed_at' => now(),
            ]);
        });
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(ComplianceInventory::class, 'inventory_id');
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(AssetItemType::class, 'asset_item_type_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ChecklistMaster::class, 'checklist_master_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ChecklistLogHistory::class, 'checklist_log_id');
    }
}
