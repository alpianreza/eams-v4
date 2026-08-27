<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\Setting;
use App\Support\Checklist\ChecklistPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Print Center (port dari CI4 CompliancePrintController).
 * Authorization is applied by the can:access-print-center route middleware.
 */
class PrintController extends Controller
{
    /** Item types yang PUNYA minimal satu inventory aktif. */
    protected function printableItemTypes()
    {
        return AssetItemType::query()
            ->whereHas('inventories', fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get();
    }

    public function index(): View
    {
        return view('print.index');
    }

    public function item(): View
    {
        return view('print.item', ['itemTypes' => $this->printableItemTypes()]);
    }

    public function inventoryByType(AssetItemType $itemType): View
    {
        return view('print._inventory-list', [
            'inventories' => $itemType->inventories()
                ->where('active', true)
                ->orderBy('asset_code')
                ->get(),
        ]);
    }

    public function batch(): View
    {
        return view('print.batch', ['itemTypes' => $this->printableItemTypes()]);
    }

    public function batchPreview(Request $request): Response
    {
        $data = $request->validate([
            'item_type_id' => ['required', 'integer', 'exists:asset_item_types,id'],
        ]);

        $itemType = AssetItemType::findOrFail((int) $data['item_type_id']);
        $month = max(1, min(12, (int) ($request->query('month') ?: now()->month)));
        $year = (int) ($request->query('year') ?: now()->year);
        $frequency = $itemType->checklist_frequency;

        $inventories = $itemType->inventories()
            ->where('active', true)
            ->with('pics')
            ->orderBy('asset_code')
            ->get();

        $masters = ChecklistMaster::query()
            ->where('asset_item_type_id', $itemType->id)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        $monthKey = sprintf('%04d-%02d', $year, $month);
        $logsQuery = ChecklistLog::query()->whereIn('inventory_id', $inventories->pluck('id'));

        if ($frequency === ChecklistPeriod::FREQ_MONTHLY) {
            $logsQuery->where('period_key', $monthKey);
        } elseif ($frequency === ChecklistPeriod::FREQ_WEEKLY) {
            $logsQuery->where('period_key', 'like', $monthKey.'-W%');
        } else {
            $logsQuery->where('period_key', 'like', $monthKey.'-%');
        }

        $logs = $logsQuery->orderByDesc('check_date')->orderByDesc('id')->get();

        $matrix = [];
        foreach ($logs as $log) {
            $current = $matrix[$log->inventory_id][$log->checklist_master_id] ?? null;
            $matrix[$log->inventory_id][$log->checklist_master_id] = $this->aggregateStatus($current, $log->status);
        }

        $findings = $this->buildFindings(
            $logs->where('status', ChecklistLog::STATUS_NOT_OK),
            $inventories,
            $masters
        );

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthLabel = $monthNames[$month];
        $periodLabel = $monthLabel.' '.$year;

        return Pdf::loadView('print.batch-form', [
            'itemType' => $itemType,
            'inventories' => $inventories,
            'masters' => $masters,
            'matrix' => $matrix,
            'findings' => $findings,
            'monthLabel' => $monthLabel,
            'year' => $year,
            'periodLabel' => $periodLabel,
            'frequency' => $frequency,
            'companyName' => Setting::value('company_name', config('eams.company_name')),
            'companyAddress' => Setting::value('company_address'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download(
            'Print-'.$itemType->name.'-'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.pdf'
        );
    }

    protected function aggregateStatus(?string $current, string $next): string
    {
        if ($current === ChecklistLog::STATUS_NOT_OK || $next === ChecklistLog::STATUS_NOT_OK) {
            return ChecklistLog::STATUS_NOT_OK;
        }
        if ($current === ChecklistLog::STATUS_OK || $next === ChecklistLog::STATUS_OK) {
            return ChecklistLog::STATUS_OK;
        }
        if ($current === ChecklistLog::STATUS_NA || $next === ChecklistLog::STATUS_NA) {
            return ChecklistLog::STATUS_NA;
        }

        return $next !== '' ? $next : (string) $current;
    }

    protected function buildFindings($logs, $inventories, $masters): array
    {
        $inventoryById = $inventories->keyBy('id');
        $questionById = $masters->pluck('question', 'id');

        return $logs->map(function (ChecklistLog $log) use ($inventoryById, $questionById): array {
            $inventory = $inventoryById[$log->inventory_id] ?? null;
            $photoPath = '';

            if ($log->photo !== null && $log->photo !== '') {
                $candidate = Storage::disk('checklist')->path($log->photo);
                $photoPath = is_file($candidate) ? $candidate : '';
            }

            return [
                'asset_code' => $inventory->asset_code ?? '-',
                'specific_area' => $inventory->specific_area ?? '-',
                'question' => $questionById[$log->checklist_master_id] ?? ('Pertanyaan #'.$log->checklist_master_id),
                'remark' => $log->remark ?? '',
                'checked_by_name' => $log->checked_by_name ?? '-',
                'check_date' => $log->check_date instanceof \DateTimeInterface
                    ? $log->check_date->format('Y-m-d')
                    : (string) ($log->check_date ?? ''),
                'period_key' => $log->period_key ?? '',
                'photo_path' => $photoPath,
            ];
        })->values()->all();
    }
}
