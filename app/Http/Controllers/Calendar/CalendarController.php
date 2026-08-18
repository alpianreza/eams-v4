<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar\CalendarEvent;
use App\Models\Holiday;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Compliance calendar: month grid combining holidays (master data) + events. */
class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $month = (string) $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $monthStart = Carbon::parse($month.'-01');
        $monthEnd = $monthStart->copy()->endOfMonth();

        // holiday_date is a pure 'Y-m-d' string → prefix match for the month.
        $holidays = Holiday::where('holiday_date', 'like', $month.'%')->get()->keyBy('holiday_date');
        $events = CalendarEvent::where('start_at', '<=', $monthEnd->copy()->endOfDay())
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('end_at')->where('start_at', '>=', $monthStart->copy()->startOfDay())
                  ->orWhere('end_at', '>=', $monthStart->copy()->startOfDay());
            })->orderBy('start_at')->get();

        return view('calendar.index', [
            'month' => $month,
            'weeks' => $this->buildWeeks($monthStart, $holidays, $events),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'sticker' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()->id;

        CalendarEvent::create($data);

        return back()->with('status', 'Event ditambahkan.');
    }

    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $event->delete();

        return back()->with('status', 'Event dihapus.');
    }

    /** Build the month grid (weeks of days) with offday + holiday + event info. */
    protected function buildWeeks(Carbon $monthStart, $holidays, $events): array
    {
        $cursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $last = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        while ($cursor->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->copy();
                $dateStr = $date->toDateString();
                $week[] = [
                    'date' => $dateStr,
                    'day' => (int) $date->format('j'),
                    'in_month' => $date->format('Y-m') === $monthStart->format('Y-m'),
                    'offday' => ChecklistPeriod::isOffday($date),
                    'holiday' => $holidays[$dateStr]->description ?? null,
                    'events' => $events->filter(fn ($e) => $this->covers($e, $date))->values(),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    protected function covers(CalendarEvent $event, Carbon $date): bool
    {
        $start = $event->start_at->copy()->startOfDay();
        $end = ($event->end_at ?? $event->start_at)->copy()->endOfDay();

        return $date->between($start, $end);
    }
}
