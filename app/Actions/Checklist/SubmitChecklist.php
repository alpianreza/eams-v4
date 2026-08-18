<?php

namespace App\Actions\Checklist;

use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Models\User;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Submit a STANDARD checklist result set (one answer per question) for an inventory
 * for the current period. (Grid is a separate official channel — 2G.)
 *
 * Rules enforced here (single place, testable):
 *  - period editable (blocks offday BR-08 / future BR-04)
 *  - status ∈ ok|not_ok|na; NA only when item type allow_na (Q-001)
 *  - NOT_OK requires remark OR photo in STANDARD mode (Q-013)
 *  - checked_by = user_id + name snapshot (Q-006)
 *  - one log-set per inventory+period(+slot) (BR-09) + history on correction (Q-023)
 */
class SubmitChecklist
{
    public static function submit(ComplianceInventory $inventory, array $answers, User $checker, string $mode = 'standard', ?Carbon $now = null): int
    {
        $now = ($now ?? Carbon::now())->copy();
        $itemType = $inventory->itemType;
        $frequency = $itemType->checklist_frequency;

        if (! ChecklistPeriod::isEditable($frequency, $now, $now)) {
            throw ValidationException::withMessages([
                'checklist' => 'Periode ini tidak dapat diisi (hari libur atau periode mendatang).',
            ]);
        }

        $periodKey = ChecklistPeriod::periodKey($frequency, $now);
        $written = 0;

        foreach ($answers as $answer) {
            $status = $answer['status'] ?? null;
            $remark = $answer['remark'] ?? null;
            $photo = $answer['photo'] ?? null;

            self::guardStatus($itemType, (string) $status, $remark, $photo, $mode);

            ChecklistLog::updateOrCreate(
                [
                    'inventory_id' => $inventory->id,
                    'checklist_master_id' => $answer['checklist_master_id'],
                    'period_key' => $periodKey,
                    'time_slot' => $answer['time_slot'] ?? null,
                ],
                [
                    'asset_item_type_id' => $itemType->id,
                    'check_date' => $now->toDateString(),
                    'status' => $status,
                    'remark' => $remark,
                    'photo' => $photo,
                    'checked_by_user_id' => $checker->id,
                    'checked_by_name' => $checker->name,
                    'mode' => $mode,
                ]
            );
            $written++;
        }

        return $written;
    }

    protected static function guardStatus(AssetItemType $itemType, string $status, ?string $remark, ?string $photo, string $mode): void
    {
        if (! in_array($status, ChecklistLog::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Status tidak valid.']);
        }

        // Q-001: NA is a valid result only when the item type allows it.
        if ($status === ChecklistLog::STATUS_NA && ! $itemType->allow_na) {
            throw ValidationException::withMessages(['status' => 'NA tidak diizinkan untuk item ini.']);
        }

        // Q-013: STANDARD mode requires remark OR photo on NOT_OK. (Grid may bypass — Q-016.)
        if ($status === ChecklistLog::STATUS_NOT_OK && $mode === 'standard' && empty($remark) && empty($photo)) {
            throw ValidationException::withMessages(['status' => 'NOT_OK wajib menyertakan keterangan atau foto.']);
        }
    }
}
