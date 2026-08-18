<?php

namespace App\Http\Controllers\Patrol;

use App\Http\Controllers\Controller;
use App\Models\Patrol\PatrolCheckpoint;
use App\Models\Patrol\PatrolLog;
use App\Models\Patrol\PatrolRoute;
use App\Models\Patrol\PatrolSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatrolController extends Controller
{
    public function index(): View
    {
        return view('patrol.index', [
            'routes' => PatrolRoute::where('active', true)->withCount('checkpoints')->orderBy('sort_order')->get(),
            'sessions' => PatrolSession::with('route')->latest('patrol_date')->latest('id')->paginate(15),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate(['patrol_route_id' => ['required', 'exists:patrol_routes,id']]);
        $route = PatrolRoute::withCount('checkpoints')->findOrFail($data['patrol_route_id']);

        $session = PatrolSession::create([
            'patrol_route_id' => $route->id,
            'patrol_date' => now()->toDateString(),
            'started_by' => $request->user()->id,
            'started_at' => now(),
            'status' => 'active',
            'total_checkpoints' => $route->checkpoints_count,
        ]);

        return redirect()->route('patrol.session', $session)->with('status', 'Sesi patrol dimulai.');
    }

    public function show(PatrolSession $session): View
    {
        $session->load('route.checkpoints');
        $doneIds = $session->logs()->pluck('patrol_checkpoint_id')->all();

        return view('patrol.session', ['session' => $session, 'doneIds' => $doneIds]);
    }

    /** Scan a checkpoint (barcode) + optional GPS; writes a patrol_log and updates the session. */
    public function scan(Request $request, PatrolSession $session): RedirectResponse
    {
        abort_unless($session->status === 'active', 422, 'Sesi patrol tidak aktif.');

        $data = $request->validate([
            'barcode_value' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'in:ok,issue'],
            'note' => ['nullable', 'string'],
        ]);

        $checkpoint = PatrolCheckpoint::where('barcode_value', $data['barcode_value'])->where('active', true)->first();
        if (! $checkpoint) {
            return back()->withErrors(['barcode_value' => 'Barcode checkpoint tidak dikenal.']);
        }

        // Checkpoint must belong to this session's route.
        if (! $session->route->checkpoints->contains($checkpoint->id)) {
            return back()->withErrors(['barcode_value' => 'Checkpoint bukan bagian dari rute sesi ini.']);
        }

        // No double-scan of the same checkpoint in one session.
        if ($session->logs()->where('patrol_checkpoint_id', $checkpoint->id)->exists()) {
            return back()->withErrors(['barcode_value' => 'Checkpoint sudah discan pada sesi ini.']);
        }

        // nullable GPS may be omitted from the request — read null-safe.
        $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lng = isset($data['longitude']) ? (float) $data['longitude'] : null;

        PatrolLog::create([
            'patrol_session_id' => $session->id,
            'patrol_route_id' => $session->patrol_route_id,
            'patrol_checkpoint_id' => $checkpoint->id,
            'checked_by' => $request->user()->id,
            'barcode_value' => $data['barcode_value'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_m' => $checkpoint->distanceFrom($lat, $lng),
            'checked_at' => now(),
        ]);

        $session->increment('checked_count');
        if ($data['status'] === 'issue') {
            $session->increment('issue_count');
        }

        // Complete the session when every checkpoint on the route is checked.
        if ($session->fresh()->isComplete()) {
            $session->update(['status' => 'completed', 'ended_at' => now()]);
        }

        return back()->with('status', 'Checkpoint '.$checkpoint->code.' tercatat.');
    }

    public function cancel(PatrolSession $session): RedirectResponse
    {
        $session->update(['status' => 'cancelled', 'ended_at' => now()]);

        return redirect()->route('patrol.index')->with('status', 'Sesi patrol dibatalkan.');
    }
}
