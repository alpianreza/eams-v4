<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Compliance dashboard KPI (§7). "late" is time-based via the period engine (Q-019). */
class DashboardController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();

        $inventories = ComplianceInventory::where('active', true)->with('itemType')->get();

        $byStatus = ['good' => 0, 'need_repair' => 0, 'not_active' => 0];
        $open = 0;
        $late = 0;

        foreach ($inventories as $inv) {
            $byStatus[$inv->status] = ($byStatus[$inv->status] ?? 0) + 1;

            $freq = $inv->itemType->checklist_frequency;
            $periodKey = ChecklistPeriod::periodKey($freq, $now);
            $hasResults = ChecklistLog::where('inventory_id', $inv->id)->where('period_key', $periodKey)->exists();
            $status = ChecklistPeriod::status($freq, $now, $hasResults, $now);

            if ($status === ChecklistPeriod::STATUS_LATE) {
                $late++;
            } elseif ($status === ChecklistPeriod::STATUS_OPEN) {
                $open++;
            }
        }

        return view('dashboard.index', [
            'total' => $inventories->count(),
            'byStatus' => $byStatus,
            'open' => $open,
            'late' => $late,
            'expired' => $inventories->filter(fn ($i) => $i->isExpired())->count(),
        ]);
    }
}
