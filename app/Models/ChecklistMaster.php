<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistMaster extends Model
{
    protected $table = 'checklist_master';

    protected $fillable = ['asset_item_type_id', 'question', 'frequency', 'require_photo', 'active'];

    protected function casts(): array
    {
        return ['require_photo' => 'boolean', 'active' => 'boolean'];
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(AssetItemType::class, 'asset_item_type_id');
    }
}
