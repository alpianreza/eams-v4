<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(): View
    {
        return view('master-data.areas.index', ['areas' => Area::latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        Area::create($data + ['active' => true]);

        return back()->with('status', 'Area ditambahkan.');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $area->update($data);

        return back()->with('status', 'Area diperbarui.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $area->delete();

        return back()->with('status', 'Area dihapus.');
    }
}
