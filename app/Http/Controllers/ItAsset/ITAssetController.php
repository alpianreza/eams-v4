<?php

namespace App\Http\Controllers\ItAsset;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ItAsset\Asset;
use App\Models\ItAsset\AssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** IT Assets (§8): asset lifecycle + assignment (BR-31/32). */
class ITAssetController extends Controller
{
    public function index(Request $request): View
    {
        $assets = Asset::with(['category', 'currentAssignment.employee'])
            ->whereHas('category', fn ($q) => $q->where('category_name', 'IT'))
            ->latest('id')->paginate(20);

        return view('it-assets.index', ['assets' => $assets]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_no' => ['required', 'string', 'max:60', 'unique:assets,inventory_no'],
            'category_id' => ['nullable', 'exists:asset_categories,id'],
            'asset_name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:aktif,dipinjam,rusak,nonaktif'],
            'location' => ['nullable', 'string', 'max:120'],
            'purchase_date' => ['nullable', 'date'],
        ]);

        Asset::create($data);

        return back()->with('status', 'Asset ditambahkan.');
    }

    public function detail(Asset $asset): View
    {
        $asset->load(['category', 'currentAssignment.employee', 'assignments.employee']);

        return view('it-assets.detail', ['asset' => $asset]);
    }

    /** BR-31: assign = close the active assignment, then create a new one. Employee must be active. */
    public function assign(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id']]);

        $employee = Employee::where('id', $data['employee_id'])->where('status', 'active')->first();
        if (! $employee) {
            return back()->withErrors(['employee_id' => 'Karyawan tidak ditemukan atau sudah nonaktif.']);
        }

        // Close the active assignment, then open a new one.
        $asset->assignments()->whereNull('returned_at')->update(['returned_at' => now()]);
        $asset->assignments()->create(['employee_id' => $employee->id, 'assigned_at' => now(), 'returned_at' => null]);

        return back()->with('status', 'Asset berhasil di-assign ke '.$employee->name.'.');
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'asset_name' => ['sometimes', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'in:aktif,dipinjam,rusak,nonaktif'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        $asset->update($data);

        // BR-31: status `rusak` → all active assignments auto-returned.
        if (($data['status'] ?? null) === 'rusak') {
            $asset->assignments()->whereNull('returned_at')->update(['returned_at' => now()]);
        }

        return back()->with('status', 'Asset diperbarui.');
    }

    public function returnAsset(Asset $asset): RedirectResponse
    {
        $asset->assignments()->whereNull('returned_at')->update(['returned_at' => now()]);

        return back()->with('status', 'Asset dikembalikan.');
    }
}
