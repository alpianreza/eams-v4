<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceInventoryPic extends Model
{
    protected $fillable = ['compliance_inventory_id', 'user_id'];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(ComplianceInventory::class, 'compliance_inventory_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
