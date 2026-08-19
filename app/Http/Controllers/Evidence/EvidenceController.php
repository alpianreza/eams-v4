<?php

namespace App\Http\Controllers\Evidence;

use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Evidence & Follow-up center (§6): not_ok findings with photo/remark + follow-up tracking. */
class EvidenceController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('follow_up_status');

        $findings = ChecklistLog::with(['inventory.itemType', 'inventory.area', 'question'])
            ->where('status', ChecklistLog::STATUS_NOT_OK)
            ->when($status, fn ($q) => $q->where('follow_up_status', $status))
            ->latest('check_date')
            ->paginate(20);

        return view('evidence.index', ['findings' => $findings, 'status' => $status]);
    }

    /** Follow-up: status open → monitoring → closed + note + date (§6). */
    public function updateFollowup(Request $request, ChecklistLog $log): RedirectResponse
    {
        $data = $request->validate([
            'follow_up_status' => ['required', 'in:open,monitoring,closed'],
            'follow_up_note' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $log->update($data);

        return back()->with('status', 'Follow-up diperbarui.');
    }
}
