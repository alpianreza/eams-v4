<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Progress monitoring (legacy-compatible, A7): per-PIC monthly progress.
 *
 * Mirrors the legacy ProgressController behavior (per user: required / done /
 * pending / late / progress % / detailMissing, sorted worst-first) with the v4
 * mappings: PIC via the compliance_inventory_pics pivot (Q-007) instead of the
 * legacy first-name string matching, and period/late computed by the single
 * ChecklistPeriod engine (BR-01..03, Q-004/Q-005) instead of duplicated helpers.
 * Reminder is delivered in-app (BR-23) via NotificationService; WhatsApp stays
 * an optional external channel behind config (docs/20 §13).
 */
class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->normalizeMonth($request->input('month'));
        $now = Carbon::now();

        return view('progress.index', [
            'month' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
            'prevMonth' => Carbon::createFromFormat('Y-m', $month)->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => Carbon::createFromFormat('Y-m', $month)->addMonthNoOverflow()->format('Y-m'),
            'rows' => $this->buildProgressRows($month, $now),
            'canRemind' => (bool) auth()->user()?->hasWriteAccess(),
        ]);
    }

    public function export(Request $request)
    {
        $month = $this->normalizeMonth($request->input('month'));
        $rows = $this->buildProgressRows($month, Carbon::now());

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['User', 'Total Inventories', 'Total Periode', 'Done', 'Pending', 'Late', 'Progress %']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['user']->name, $row['totalInventory'], $row['required'],
                $row['done'], $row['pending'], $row['late'], $row['progress'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"progress-{$month}.csv\"",
        ]);
    }

    /** BR-23: send an in-app reminder containing the missing-period list. */
    public function remind(Request $request, User $user)
    {
        $month = $this->normalizeMonth($request->input('month'));
        $summary = $this->summarizeUser($user, $month, Carbon::now());

        if (empty($summary['detailMissing'])) {
            return back()->with('warning', "{$user->name} tidak punya checklist pending untuk periode {$month}.");
        }

        $lines = [];
        foreach ($summary['detailMissing'] as $i => $row) {
            $lines[] = ($i + 1) . '. ' . $row['inventory'] . ' [' . $row['frequency'] . '] - missing: ' . implode(', ', $row['missing']);
        }

        NotificationService::notify(
            $user,
            "Reminder checklist {$month}",
            "Masih ada checklist yang belum diisi:\n" . implode("\n", $lines),
            route('home'),
            'warning'
        );

        return back()->with('status', "Reminder terkirim ke {$user->name} untuk periode {$month}.");
    }

    /** Per-PIC rows sorted worst-first (legacy: ascending progress). */
    protected function buildProgressRows(string $month, Carbon $now): array
    {
        [$daily, $weekly, $monthly] = $this->monthPeriods($month, $now);

        $inventories = ComplianceInventory::where('active', true)
            ->with(['itemType', 'area', 'pics'])
            ->orderBy('asset_code')
            ->get();

        $byUser = [];
        $allIds = [];
        foreach ($inventories as $inv) {
            foreach ($inv->pics as $pic) {
                $byUser[$pic->id][] = $inv;
                $allIds[$inv->id] = true;
            }
        }

        $lookup = $this->logLookup(array_keys($allIds), $month);

        $users = User::query()
            ->where('status', 'active')
            ->whereNotIn('role', ['auditor'])
            ->where('username', '!=', 'admin')
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($users as $user) {
            $userInventories = collect($byUser[$user->id] ?? []);
            if ($userInventories->isEmpty()) {
                continue; // legacy: users without assigned inventories are skipped
            }

            $summary = $this->summarizeInventories($userInventories, $lookup, $daily, $weekly, $monthly, $now);

            $rows[] = [
                'user' => $user,
                'totalInventory' => $userInventories->count(),
                'required' => $summary['required'],
                'done' => $summary['done'],
                'pending' => $summary['pending'],
                'late' => $summary['late'],
                'progress' => $summary['progress'],
                'detailMissing' => $summary['detailMissing'],
            ];
        }

        usort($rows, fn (array $a, array $b) => [$a['progress'], $a['user']->name] <=> [$b['progress'], $b['user']->name]);

        return $rows;
    }

    /** Summary for one user (remind + detail modal). */
    protected function summarizeUser(User $user, string $month, Carbon $now): array
    {
        [$daily, $weekly, $monthly] = $this->monthPeriods($month, $now);

        $userInventories = ComplianceInventory::where('active', true)
            ->whereHas('pics', fn ($q) => $q->where('users.id', $user->id))
            ->with(['itemType', 'area'])
            ->orderBy('asset_code')
            ->get();

        $lookup = $this->logLookup($userInventories->pluck('id')->all(), $month);

        return $this->summarizeInventories($userInventories, $lookup, $daily, $weekly, $monthly, $now);
    }

    /**
     * Active periods for the month (legacy semantics):
     * daily = non-offday days capped at today for the running month (BR-07/08);
     * weekly = W1..current week (month slices, BR-02); monthly = the month itself.
     *
     * @return array{0: list<array{key:string,label:string,date:Carbon}>, 1: list<array{key:string,label:string,date:Carbon}>, 2: list<array{key:string,label:string,date:Carbon}>}
     */
    protected function monthPeriods(string $month, Carbon $now): array
    {
        $isCurrentMonth = $month === $now->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfDay();
        $maxDay = $isCurrentMonth ? (int) $now->format('j') : (int) $monthStart->copy()->endOfMonth()->format('j');

        $daily = [];
        for ($d = 1; $d <= $maxDay; $d++) {
            $date = $monthStart->copy()->addDays($d - 1);
            if (ChecklistPeriod::isOffday($date)) {
                continue;
            }
            $daily[] = ['key' => $date->toDateString(), 'label' => $date->format('d'), 'date' => $date];
        }

        $currentWeek = $isCurrentMonth
            ? min(4, max(1, (int) ceil((int) $now->format('j') / 7)))
            : 4;
        $weekly = [];
        for ($w = 1; $w <= $currentWeek; $w++) {
            $weekly[] = ['key' => $month.'-W'.$w, 'label' => 'W'.$w, 'date' => $monthStart->copy()->addDays(($w - 1) * 7)];
        }

        $monthly = [['key' => $month, 'label' => 'Belum', 'date' => $monthStart->copy()->endOfMonth()->startOfDay()]];

        return [$daily, $weekly, $monthly];
    }

    /** One lookup for all inventories of the month: [inventory_id][period_key] = true. */
    protected function logLookup(array $inventoryIds, string $month): array
    {
        if ($inventoryIds === []) {
            return [];
        }

        $lookup = [];
        ChecklistLog::whereIn('inventory_id', $inventoryIds)
            ->where('period_key', 'like', $month.'%')
            ->get(['inventory_id', 'period_key'])
            ->each(function (ChecklistLog $log) use (&$lookup) {
                $lookup[$log->inventory_id][$log->period_key] = true;
            });

        return $lookup;
    }

    /**
     * Aggregate required/done/pending/late/progress/detailMissing over inventories.
     *
     * @param  iterable<ComplianceInventory>  $inventories
     */
    protected function summarizeInventories(iterable $inventories, array $lookup, array $daily, array $weekly, array $monthly, Carbon $now): array
    {
        $required = 0;
        $done = 0;
        $pending = 0;
        $late = 0;
        $detailMissing = [];

        foreach ($inventories as $inv) {
            $frequency = strtolower((string) ($inv->itemType->checklist_frequency ?? 'monthly'));
            $periods = match ($frequency) {
                'daily' => $daily,
                'weekly' => $weekly,
                default => $monthly,
            };

            $missing = [];
            foreach ($periods as $period) {
                $required++;

                if (! empty($lookup[$inv->id][$period['key']])) {
                    $done++;
                    continue;
                }

                $pending++;
                $missing[] = $period['label'];

                if (ChecklistPeriod::status($frequency, $period['date'], false, $now) === ChecklistPeriod::STATUS_LATE) {
                    $late++;
                }
            }

            if ($missing !== []) {
                $detailMissing[] = [
                    'inventory' => ($inv->itemType->name ?? 'Item') . ' - ' . ($inv->specific_area ?? '-'),
                    'frequency' => ucfirst($frequency),
                    'missing' => $missing,
                ];
            }
        }

        $progress = $required > 0 ? (int) round($done / $required * 100) : 0;

        return [
            'required' => $required,
            'done' => $done,
            'pending' => $pending,
            'late' => $late,
            'progress' => $progress,
            'detailMissing' => $detailMissing,
        ];
    }

    protected function normalizeMonth(?string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', (string) $month) ? (string) $month : Carbon::now()->format('Y-m');
    }
}
