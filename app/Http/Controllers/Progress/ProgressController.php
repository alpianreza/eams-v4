<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Progress monitoring (§7): current-period status per inventory via the period engine (Q-019: time-based late). */
class ProgressController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();

        $inventories = ComplianceInventory::where('active', true)
            ->with(['itemType', 'area', 'pics'])
            ->orderBy('asset_code')
            ->paginate(25);

        $rows = $inventories->map(function ($inv) use ($now) {
            $freq = $inv->itemType->checklist_frequency;
            $periodKey = ChecklistPeriod::periodKey($freq, $now);
            $hasResults = ChecklistLog::where('inventory_id', $inv->id)->where('period_key', $periodKey)->exists();
            $status = ChecklistPeriod::status($freq, $now, $hasResults, $now);

            return [
                'inventory' => $inv,
                'period_key' => $periodKey,
                'status' => $status,
                'last_check' => ChecklistLog::where('inventory_id', $inv->id)->latest('check_date')->value('check_date'),
            ];
        });

        return view('progress.index', ['rows' => $rows, 'inventories' => $inventories]);
    }
}
