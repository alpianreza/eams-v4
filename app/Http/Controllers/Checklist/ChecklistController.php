<?php

namespace App\Http\Controllers\Checklist;

use App\Actions\Checklist\SubmitChecklist;
use App\Http\Controllers\Controller;
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

        return view('checklist.fill', [
            'inventory' => $inventory,
            'questions' => $questions,
            'periodKey' => ChecklistPeriod::periodKey($frequency, $now),
            'editable' => ChecklistPeriod::isEditable($frequency, $now, $now),
            'allowNa' => (bool) $inventory->itemType->allow_na,
        ]);
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
