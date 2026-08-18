<?php

namespace App\Http\Controllers\Fdm;

use App\Http\Controllers\Controller;
use App\Models\Fdm\FdmProductionSectionYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** FDM Data Collection (2K, Compliance): production-section entries with monthly values per year. */
class FdmDataCollectionController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);
        $yearModel = FdmProductionSectionYear::with('entries')->firstOrCreate(['report_year' => $year]);

        return view('fdm.index', ['year' => $year, 'yearModel' => $yearModel]);
    }

    public function saveEntry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'report_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'section_key' => ['required', 'string', 'max:80'],
            'section_label' => ['nullable', 'string', 'max:160'],
            'entry_type' => ['nullable', 'string', 'max:80'],
            'frequency_label' => ['nullable', 'string', 'max:80'],
            'monthly_values' => ['nullable', 'array'],
            'monthly_values.*' => ['nullable', 'numeric'],
        ]);

        $year = FdmProductionSectionYear::firstOrCreate(['report_year' => $data['report_year']]);

        $year->entries()->updateOrCreate(
            ['section_key' => $data['section_key']],
            [
                'section_label' => $data['section_label'] ?? null,
                'entry_type' => $data['entry_type'] ?? null,
                'frequency_label' => $data['frequency_label'] ?? null,
                'monthly_values' => $data['monthly_values'] ?? null,
            ]
        );

        return redirect()->route('fdm.index', ['year' => $data['report_year']])->with('status', 'Entri FDM tersimpan.');
    }
}
