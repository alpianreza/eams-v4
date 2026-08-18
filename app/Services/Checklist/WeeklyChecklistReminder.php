<?php

namespace App\Services\Checklist;

use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;

/**
 * Weekly checklist reminder (BR-23/24). Reminds PICs of pending checklists via in-app
 * notification. Rules: do NOT send on an offday (uses the unified period engine), and
 * only remind for DUE periods (OPEN/LATE) — never FUTURE (not-yet-due) or HOLIDAY.
 */
class WeeklyChecklistReminder
{
    /** Inventories with a due-but-unfinished checklist for which $user is a PIC. */
    public function pendingForUser(User $user, ?Carbon $now = null): array
    {
        $now = ($now ?? Carbon::now())->copy();

        $inventories = ComplianceInventory::whereHas('pics', fn ($q) => $q->where('users.id', $user->id))
            ->where('active', true)
            ->with('itemType')
            ->get();

        $pending = [];
        foreach ($inventories as $inventory) {
            $frequency = $inventory->itemType->checklist_frequency;
            $status = ChecklistPeriod::status($frequency, $now, $this->hasResults($inventory, $frequency, $now), $now);

            // Remind only for DUE (open/late) periods — not DONE, FUTURE, or HOLIDAY (BR-23/24).
            if (in_array($status, [ChecklistPeriod::STATUS_OPEN, ChecklistPeriod::STATUS_LATE], true)) {
                $pending[] = $inventory;
            }
        }

        return $pending;
    }

    /** Send reminders to all active users. Returns the number of notifications created. */
    public function send(?Carbon $now = null): int
    {
        $now = ($now ?? Carbon::now())->copy();

        // BR-23/24: never send reminders on an offday (weekend/holiday).
        if (ChecklistPeriod::isOffday($now)) {
            return 0;
        }

        $sent = 0;
        foreach (User::where('status', 'active')->get() as $user) {
            foreach ($this->pendingForUser($user, $now) as $inventory) {
                NotificationService::notify(
                    $user,
                    'Checklist pending: '.$inventory->asset_code,
                    'Checklist '.$inventory->itemType->name.' untuk periode ini belum diisi.',
                    route('compliance.checklist.fill', $inventory),
                    'checklist_reminder',
                );
                $sent++;
            }
        }

        return $sent;
    }

    protected function hasResults(ComplianceInventory $inventory, string $frequency, Carbon $now): bool
    {
        $periodKey = ChecklistPeriod::periodKey($frequency, $now);

        return ChecklistLog::where('inventory_id', $inventory->id)->where('period_key', $periodKey)->exists();
    }
}
