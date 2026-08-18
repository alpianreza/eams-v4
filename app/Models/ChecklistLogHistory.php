<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistLogHistory extends Model
{
    /** Append-only audit trail — no updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'checklist_log_id', 'changed_by_user_id', 'changed_by_name',
        'old_status', 'new_status', 'old_remark', 'new_remark',
        'old_photo', 'new_photo', 'changed_at',
    ];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(ChecklistLog::class, 'checklist_log_id');
    }
}
