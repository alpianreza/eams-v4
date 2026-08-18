<?php

namespace App\Http\Controllers\Ems;

use App\Http\Controllers\Controller;
use App\Models\Ems\EmsElectricConsumptionEntry;
use App\Models\Ems\EmsElectricConsumptionYear;
use App\Models\Ems\EmsMobileCombustionEntry;
use App\Models\Ems\EmsMobileCombustionYear;
use App\Models\Ems\EmsStationaryCombustionEntry;
use App\Models\Ems\EmsStationaryCombustionYear;
use App\Models\Ems\EmsWaterConsumptionEntry;
use App\Models\Ems\EmsWaterConsumptionYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EMS/GHG report (2K, Compliance): monthly consumption per section per year + year meta. */
class EmsReportController extends Controller
{
    protected const CATEGORIES = [
        'water' => ['label' => 'Konsumsi Air', 'entry' => EmsWaterConsumptionEntry::class, 'year' => EmsWaterConsumptionYear::class],
        'electric' => ['label' => 'Konsumsi Listrik', 'entry' => EmsElectricConsumptionEntry::class, 'year' => EmsElectricConsumptionYear::class],
        'stationary' => ['label' => 'Stationary Combustion', 'entry' => EmsStationaryCombustionEntry::class, 'year' => EmsStationaryCombustionYear::class],
        'mobile' => ['label' => 'Mobile Combustion', 'entry' => EmsMobileCombustionEntry::class, 'year' => EmsMobileCombustionYear::class],
    ];

    public function index(string $category, Request $request): View
    {
        [$label, $entryClass, $yearClass] = $this->config($category);
        $year = (int) $request->input('year', now()->year);

        return view('ems.index', [
            'category' => $category,
            'label' => $label,
            'year' => $year,
            'matrix' => $entryClass::matrixForYear($year),
            'yearMeta' => $yearClass::where('report_year', $year)->first(),
            'categories' => collect(self::CATEGORIES)->map(fn ($c) => $c['label']),
        ]);
    }

    public function saveEntry(string $category, Request $request): RedirectResponse
    {
        [, $entryClass] = $this->config($category);

        $data = $request->validate([
            'report_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'section_key' => ['required', 'string', 'max:80'],
            'report_month' => ['required', 'integer', 'min:1', 'max:12'],
            'consumption_amount' => ['nullable', 'numeric'],
        ]);

        // upsert — one entry per section+month+year (legacy unique key).
        $entryClass::updateOrCreate(
            ['report_year' => $data['report_year'], 'section_key' => $data['section_key'], 'report_month' => $data['report_month']],
            ['consumption_amount' => $data['consumption_amount'] ?? null],
        );

        return redirect()->route('ems.index', ['category' => $category, 'year' => $data['report_year']])->with('status', 'Entri tersimpan.');
    }

    public function saveYear(string $category, Request $request): RedirectResponse
    {
        [, , $yearClass] = $this->config($category);

        $data = $request->validate([
            'report_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'production_output' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $yearClass::updateOrCreate(['report_year' => $data['report_year']], ['production_output' => $data['production_output'] ?? null, 'notes' => $data['notes'] ?? null]);

        return redirect()->route('ems.index', ['category' => $category, 'year' => $data['report_year']])->with('status', 'Metadata tahun tersimpan.');
    }

    protected function config(string $category): array
    {
        abort_unless(isset(self::CATEGORIES[$category]), 404);
        $c = self::CATEGORIES[$category];

        return [$c['label'], $c['entry'], $c['year']];
    }
}
