<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Models\Utility\BoilerFuelLog;
use App\Models\Utility\IpalLog;
use App\Models\Utility\PdamWaterBoilerLog;
use App\Models\Utility\PdamWaterLog;
use App\Models\Utility\UtilityDailyLog;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Shared controller for the Boiler & Utility daily logs (BR-29/30):
 * month grid (rows = days, offdays colored via the period engine), monthly total,
 * daily entry (some types 1/day), delete.
 */
class UtilityLogController extends Controller
{
    /** type => [model, value label, value input name, unit]. */
    protected const TYPES = [
        'boiler' => [BoilerFuelLog::class, 'Bahan Bakar Boiler', 'kg', 'Kg'],
        'pdam-water' => [PdamWaterLog::class, 'Air PDAM', 'meter_reading', 'm³'],
        'pdam-water-boiler' => [PdamWaterBoilerLog::class, 'Air PDAM Boiler', 'meter_reading', 'm³'],
        'ipal' => [IpalLog::class, 'IPAL Limbah', 'value', ''],
    ];

    public function index(string $type, Request $request): View
    {
        [$class, $label, $valueField, $unit] = $this->config($type);
        $month = $this->month($request);

        $logs = $class::forMonth($month)->orderBy('log_date')->get()->keyBy('log_date');
        $total = $class::monthlyTotal($month);

        return view('utility.index', [
            'type' => $type, 'label' => $label, 'valueField' => $valueField, 'unit' => $unit,
            'month' => $month, 'logs' => $logs, 'total' => $total,
            'days' => $this->daysOfMonth($month),
        ]);
    }

    public function store(string $type, Request $request): RedirectResponse
    {
        [$class, $label, $valueField] = $this->config($type);

        $data = $request->validate([
            'log_date' => ['required', 'date'],
            'log_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string'],
            $valueField => ['nullable', 'numeric'],
            'polybag' => ['nullable', 'integer'],
        ]);

        $data['created_by'] = $request->user()->name;

        $query = $class::where('log_date', $data['log_date']);
        // pdam-water-boiler is 1-per-day (legacy unique log_date).
        if ($class === PdamWaterBoilerLog::class && $query->exists()) {
            return back()->withErrors(['log_date' => 'Data untuk tanggal ini sudah ada (1 data per hari).'])->withInput();
        }

        $class::create($data);

        return redirect()->route('utility.index', ['type' => $type, 'month' => substr($data['log_date'], 0, 7)])
            ->with('status', 'Log '.$label.' tersimpan.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        [$class] = $this->config($type);
        $log = $class::findOrFail($id);
        $month = substr($log->log_date, 0, 7);
        $log->delete();

        return redirect()->route('utility.index', ['type' => $type, 'month' => $month])->with('status', 'Log dihapus.');
    }

    protected function config(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    protected function month(Request $request): string
    {
        $month = (string) $request->input('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }

    /** Days of the month with offday flags (via the unified period engine — BR-29 coloring). */
    protected function daysOfMonth(string $ym): array
    {
        $start = Carbon::parse($ym.'-01');
        $count = $start->daysInMonth;
        $days = [];
        for ($d = 1; $d <= $count; $d++) {
            $date = $start->copy()->day($d);
            $days[] = ['date' => $date->toDateString(), 'day' => $d, 'offday' => ChecklistPeriod::isOffday($date)];
        }

        return $days;
    }
}
