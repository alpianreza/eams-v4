<?php

namespace App\Http\Controllers\Thermal;

use App\Http\Controllers\Controller;
use App\Models\Thermal\ThermalImagingLocation;
use App\Models\Thermal\ThermalImagingReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Thermal Imaging (2K, Compliance): inspection reports with per-location items. */
class ThermalImagingController extends Controller
{
    public function index(): View
    {
        return view('thermal.index', [
            'reports' => ThermalImagingReport::withCount('items')->latest('inspection_date')->paginate(15),
            'locations' => ThermalImagingLocation::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inspection_date' => ['required', 'date'],
            'inspector_name' => ['nullable', 'string', 'max:120'],
            'facility' => ['nullable', 'string', 'max:120'],
            'area_name' => ['nullable', 'string', 'max:120'],
        ]);
        $data['created_by'] = $request->user()->id;

        $report = ThermalImagingReport::create($data);

        return redirect()->route('thermal.show', $report)->with('status', 'Report thermal dibuat. Tambahkan item.');
    }

    public function show(ThermalImagingReport $report): View
    {
        $report->load('items');

        return view('thermal.show', ['report' => $report, 'locations' => ThermalImagingLocation::where('active', true)->orderBy('name')->get()]);
    }

    public function addItem(Request $request, ThermalImagingReport $report): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['nullable', 'exists:thermal_imaging_locations,id'],
            'location_name' => ['nullable', 'string', 'max:160'],
            'celsius' => ['nullable', 'numeric'],
            'findings' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ]);
        $data['sort_order'] = (int) $report->items()->max('sort_order') + 1;
        if (empty($data['location_name']) && ! empty($data['location_id'])) {
            $data['location_name'] = ThermalImagingLocation::find($data['location_id'])?->name;
        }

        $report->items()->create($data);

        return back()->with('status', 'Item thermal ditambahkan.');
    }
}
