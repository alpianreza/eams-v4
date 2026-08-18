<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Support\Checklist\ChecklistPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compliance report PDF (2I). Authorization is via the `access-compliance-pdf` GATE
 * on the route (Q-008: admin + users with Compliance access only — not hard-coded roles).
 * Library: Dompdf (single library — removes the legacy mPDF/Dompdf duplication).
 */
class ComplianceReportController extends Controller
{
    public function pdf(Request $request, ComplianceInventory $inventory): Response
    {
        $inventory->load(['itemType', 'area', 'category', 'pics']);
        $itemType = $inventory->itemType;
        $now = Carbon::now();
        $periodKey = ChecklistPeriod::periodKey($itemType->checklist_frequency, $now);

        $logs = ChecklistLog::with('question')
            ->where('inventory_id', $inventory->id)
            ->where('period_key', $periodKey)
            ->orderBy('checklist_master_id')
            ->get();

        // Per-item-type print form resolved by `code` (Q-015), with a generic fallback.
        $view = $this->resolveView($itemType->code);

        return Pdf::loadView($view, [
            'inventory' => $inventory,
            'logs' => $logs,
            'periodKey' => $periodKey,
            'generatedAt' => $now,
        ])->download('checklist-'.$inventory->asset_code.'-'.$periodKey.'.pdf');
    }

    /** Resolve a per-item-type print view by code, falling back to the generic form. */
    protected function resolveView(string $code): string
    {
        $specific = 'report.forms.'.strtolower($code);

        return view()->exists($specific) ? $specific : 'report.compliance-pdf';
    }
}
