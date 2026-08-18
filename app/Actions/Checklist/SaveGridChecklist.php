<?php

namespace App\Actions\Checklist;

use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\User;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * GRID checklist (Q-016: official fast/mass-entry channel — NOT a workaround).
 * Produces OK/NOT_OK/NA (NA still follows allow_na). Grid MAY bypass the NOT_OK
 * evidence validation (Q-013) for speed (e.g. 20+ P3K daily).
 */
class SaveGridChecklist
{
    /** Mass-set one inventory's answers via grid. Evidence validation bypassed (Q-016). */
    public static function set(ComplianceInventory $inventory, array $answers, User $checker, ?Carbon $now = null): int
    {
        return SubmitChecklist::submit($inventory, $answers, $checker, 'grid', $now);
    }

    /** BR-15: mark-all fills ONLY empty cells for the current period — never overwrites. */
    public static function markAll(AssetItemType $itemType, string $status, User $checker, ?Carbon $now = null): int
    {
        $now = ($now ?? Carbon::now())->copy();

        if (! in_array($status, ChecklistLog::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Status tidak valid.']);
        }

        // NA still respects allow_na even via mark-all (Q-001/Q-016).
        if ($status === ChecklistLog::STATUS_NA && ! $itemType->allow_na) {
            throw ValidationException::withMessages(['status' => 'NA tidak diizinkan untuk item ini.']);
        }

        $periodKey = ChecklistPeriod::periodKey($itemType->checklist_frequency, $now);
        $questions = ChecklistMaster::where('asset_item_type_id', $itemType->id)->where('active', true)->get();
        $inventories = $itemType->inventories()->where('active', true)->get();
        $written = 0;

        foreach ($inventories as $inventory) {
            foreach ($questions as $q) {
                $exists = ChecklistLog::where('inventory_id', $inventory->id)
                    ->where('checklist_master_id', $q->id)
                    ->where('period_key', $periodKey)
                    ->whereNull('time_slot')
                    ->exists();

                if ($exists) {
                    continue; // BR-15: never overwrite an existing cell
                }

                ChecklistLog::create([
                    'inventory_id' => $inventory->id,
                    'asset_item_type_id' => $itemType->id,
                    'checklist_master_id' => $q->id,
                    'check_date' => $now->toDateString(),
                    'period_key' => $periodKey,
                    'time_slot' => null,
                    'status' => $status,
                    'checked_by_user_id' => $checker->id,
                    'checked_by_name' => $checker->name,
                    'mode' => 'grid',
                ]);
                $written++;
            }
        }

        return $written;
    }

    /** BR-16: clear removes the current period's grid cells for the item type. */
    public static function clear(AssetItemType $itemType, ?Carbon $now = null): int
    {
        $now = ($now ?? Carbon::now())->copy();
        $periodKey = ChecklistPeriod::periodKey($itemType->checklist_frequency, $now);

        return ChecklistLog::where('asset_item_type_id', $itemType->id)
            ->where('period_key', $periodKey)
            ->where('mode', 'grid')
            ->delete();
    }
}
