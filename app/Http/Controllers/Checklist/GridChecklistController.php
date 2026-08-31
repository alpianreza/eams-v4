<?php

namespace App\Http\Controllers\Checklist;

use App\Actions\Checklist\SaveGridChecklist;
use App\Http\Controllers\Controller;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class GridChecklistController extends Controller
{
    /** Grid view: matrix of active inventories × questions for an item type (current period). */
    public function show(AssetItemType $itemType): View
    {
        $now = Carbon::now();
        $periodKey = ChecklistPeriod::periodKey($itemType->checklist_frequency, $now);

        $inventories = $itemType->inventories()->where('active', true)->orderBy('asset_code')->get();
        $questions = $itemType->checklistQuestions()->where('active', true)->orderBy('id')->get();

        // existing grid answers for the period: [inventory_id][checklist_master_id] => status
        $existing = ChecklistLog::where('asset_item_type_id', $itemType->id)
            ->where('period_key', $periodKey)
            ->get()
            ->groupBy('inventory_id')
            ->map(fn ($rows) => $rows->keyBy('checklist_master_id')->map->status);

        return view('checklist.grid', [
            'itemType' => $itemType,
            'inventories' => $inventories,
            'questions' => $questions,
            'existing' => $existing,
            'periodKey' => $periodKey,
            'allowNa' => (bool) $itemType->allow_na,
        ]);
    }

    /** Mass-set the grid cells for an item type (grid mode — bypasses evidence, Q-016). */
    public function set(Request $request, AssetItemType $itemType): RedirectResponse
    {
        $inventories = $itemType->inventories()->where('active', true)->get();
        $questions = $itemType->checklistQuestions()->where('active', true)->get();
        $written = 0;

        foreach ($inventories as $inventory) {
            $answers = [];
            foreach ($questions as $q) {
                $status = $request->input("cell_{$inventory->id}_{$q->id}");
                if ($status === null || $status === '') {
                    continue;
                }
                $answers[] = [
                    'checklist_master_id' => $q->id,
                    'status' => $status,
                    'remark' => null,
                    'photo' => null,
                    'time_slot' => $request->input("slot_{$inventory->id}"),
                ];
            }
            if ($answers !== []) {
                $written += SaveGridChecklist::set($inventory, $answers, $request->user());
            }
        }

        return back()->with('status', "Grid tersimpan ({$written} sel).");
    }

    public function markAll(Request $request, AssetItemType $itemType): RedirectResponse
    {
        $status = (string) $request->input('status', 'ok');
        $written = SaveGridChecklist::markAll($itemType, $status, $request->user(), timeSlot: $request->input('time_slot'));

        return back()->with('status', "Mark-all mengisi {$written} sel kosong.");
    }

    public function clear(Request $request, AssetItemType $itemType): RedirectResponse
    {
        $deleted = SaveGridChecklist::clear($itemType, timeSlot: $request->input('time_slot'));

        return back()->with('status', "Clear menghapus {$deleted} sel.");
    }
}
