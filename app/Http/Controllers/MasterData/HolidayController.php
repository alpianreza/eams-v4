<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        return view('master-data.holidays.index', ['holidays' => Holiday::orderByDesc('holiday_date')->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Holiday::create($data);

        return back()->with('status', 'Hari libur ditambahkan.');
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date,'.$holiday->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $holiday->update($data);

        return back()->with('status', 'Hari libur diperbarui.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', 'Hari libur dihapus.');
    }
}
