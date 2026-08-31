<?php

namespace App\Http\Controllers\Checklist;

use App\Actions\Checklist\SubmitChecklist;
use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ChecklistController extends Controller
{
    /** Standard checklist fill form for one inventory (current period). */
    public function fill(ComplianceInventory $inventory): View
    {
        $inventory->load('itemType');
        $questions = ChecklistMaster::where('asset_item_type_id', $inventory->asset_item_type_id)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        $now = Carbon::now();
        $frequency = $inventory->itemType->checklist_frequency;

        // Build PeriodStrip data
        $periods = $this->buildPeriodStrip($inventory, $now, $frequency);
        $currentKey = ChecklistPeriod::periodKey($frequency, $now);
        [$month, $year] = $this->extractMonthYear($frequency, $now);

        return view('checklist.fill', [
            'inventory' => $inventory,
            'questions' => $questions,
            'periodKey' => $currentKey,
            'editable' => ChecklistPeriod::isEditable($frequency, $now, $now),
            'allowNa' => (bool) $inventory->itemType->allow_na,
            'periodStrip' => [
                'periods' => $periods,
                'currentKey' => $currentKey,
                'month' => $month,
                'year' => $year,
                'frequency' => $frequency,
            ],
        ]);
    }

    /**
     * Build period strip entries with status, editability, and URLs.
     * Returns array of ['key', 'label', 'status', 'editable', 'url', 'active', 'disabled', 'reason'].
     */
    protected function buildPeriodStrip(ComplianceInventory $inventory, Carbon $now, string $frequency): array
    {
        $periods = [];
        $baseDate = $now->copy();
        $lookback = $this->lookbackMonths($frequency);

        // Start from lookback months ago to current period
        $startDate = $baseDate->copy()->subMonths($lookback)->startOfMonth();

        // For daily, iterate each day; for weekly, each week; for monthly, each month
        $date = $startDate->copy();
        $endDate = $baseDate->copy()->endOfMonth();

        $existingKeys = ChecklistLog::where('inventory_id', $inventory->id)
            ->where('asset_item_type_id', $inventory->asset_item_type_id)
            ->whereBetween('check_date', [$startDate, $endDate])
            ->get()
            ->groupBy('period_key')
            ->keys()
            ->toArray();

        while ($date->lte($endDate)) {
            $periodKey = ChecklistPeriod::periodKey($frequency, $date);
            $hasResults = in_array($periodKey, $existingKeys);
            $status = ChecklistPeriod::status($frequency, $date, $hasResults, $now);
            $editable = ChecklistPeriod::isEditable($frequency, $date, $now);

            $periods[] = [
                'key' => $periodKey,
                'label' => $this->formatPeriodLabel($frequency, $date),
                'status' => $status,
                'editable' => $editable,
                'url' => null,
                'active' => $periodKey === ChecklistPeriod::periodKey($frequency, $now),
                'disabled' => ! $editable,
                'reason' => ! $editable ? 'Periode terkunci' : null,
            ];

            $this->advanceDate($frequency, $date);
        }

        return $periods;
    }

    protected function lookbackMonths(string $frequency): int
    {
        return match ($frequency) {
            ChecklistPeriod::FREQ_DAILY => 2,   // Show 2 months back for daily
            ChecklistPeriod::FREQ_WEEKLY => 3,  // Show 3 months back for weekly
            ChecklistPeriod::FREQ_MONTHLY => 12, // Show 12 months back for monthly
            default => 3,
        };
    }

    protected function formatPeriodLabel(string $frequency, Carbon $date): string
    {
        return match ($frequency) {
            ChecklistPeriod::FREQ_DAILY => $date->format('d M'),
            ChecklistPeriod::FREQ_WEEKLY => 'W' . ChecklistPeriod::weekOfMonth($date),
            ChecklistPeriod::FREQ_MONTHLY => $date->format('M Y'),
            default => $date->format('Y-m-d'),
        };
    }

    protected function extractMonthYear(string $frequency, Carbon $now): array
    {
        return [$now->month, $now->year];
    }

    protected function advanceDate(string $frequency, Carbon &$date): void
    {
        match ($frequency) {
            ChecklistPeriod::FREQ_DAILY => $date->addDay(),
            ChecklistPeriod::FREQ_WEEKLY => $date->addWeek(),
            ChecklistPeriod::FREQ_MONTHLY => $date->addMonth(),
            default => $date->addDay(),
        };
    }

    public function store(Request $request, ComplianceInventory $inventory): RedirectResponse
    {
        $inventory->load('itemType');
        $questions = ChecklistMaster::where('asset_item_type_id', $inventory->asset_item_type_id)
            ->where('active', true)->get();

        $answers = [];
        foreach ($questions as $q) {
            $status = $request->input("status_{$q->id}");
            if ($status === null || $status === '') {
                continue; // unanswered question is skipped
            }

            $photoPath = null;
            if ($request->hasFile("photo_{$q->id}")) {
                $request->validate(["photo_{$q->id}" => ['image', 'max:5120']]);
                $photoPath = $request->file("photo_{$q->id}")->store('', 'checklist'); // configurable disk (Q-022)
            }

            $answers[] = [
                'checklist_master_id' => $q->id,
                'status' => $status,
                'remark' => $request->input("remark_{$q->id}"),
                'photo' => $photoPath,
                'time_slot' => $request->input('time_slot'),
            ];
        }

        SubmitChecklist::submit($inventory, $answers, $request->user(), 'standard');

        return back()->with('status', 'Checklist tersimpan.');
    }
}
