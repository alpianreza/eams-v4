<?php

namespace App\Livewire\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\Holiday;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Index extends Component
{
    public string $month;

    public function mount(?string $month = null): void
    {
        $this->month = $month ?? Carbon::now()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = Carbon::now()->format('Y-m');
        }
    }

    public function setMonth(string $month): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->month = $month;
        }
    }

    public function render()
    {
        $monthStart = Carbon::parse($this->month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $now = Carbon::now();

        // holidays for the month
        $holidays = Holiday::where('holiday_date', 'like', $this->month.'%')
            ->get()
            ->keyBy('holiday_date');

        // events overlapping the month
        $events = CalendarEvent::query()
            ->where('start_at', '<=', $monthEnd->copy()->endOfDay())
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('end_at')
                  ->where('start_at', '>=', $monthStart->copy()->startOfDay())
                  ->orWhere('end_at', '>=', $monthStart->copy()->startOfDay());
            })
            ->orderBy('start_at')
            ->get();

        $weeks = $this->buildWeeks($monthStart, $holidays, $events, $now);

        return view('livewire.calendar.index', [
            'month' => $this->month,
            'monthStart' => $monthStart,
            'weeks' => $weeks,
            'hasWriteAccess' => auth()->user()?->hasWriteAccess() ?? false,
        ]);
    }

    /** Build the month grid (weeks of days) with offday + holiday + event info. */
    protected function buildWeeks(Carbon $monthStart, $holidays, $events, Carbon $now): array
    {
        $cursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $last = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        while ($cursor->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->copy();
                $dateStr = $date->toDateString();

                $today = $date->format('Y-m-d') === $now->format('Y-m-d');
                $periodKey = ChecklistPeriod::periodKey(ChecklistPeriod::FREQ_DAILY, $date);
                $hasResults = ChecklistPeriod::status(ChecklistPeriod::FREQ_DAILY, $date, true, $now) === ChecklistPeriod::STATUS_DONE;

                // For today: check if there are actual logs
                // We approximate 'done' based on date being in past
                $hasResults = ! $date->isFuture() && $date->lt($now);

                $status = ChecklistPeriod::status(ChecklistPeriod::FREQ_DAILY, $date, $hasResults, $now);
                $editable = ChecklistPeriod::isEditable(ChecklistPeriod::FREQ_DAILY, $date, $now);

                $week[] = [
                    'date' => $dateStr,
                    'day' => (int) $date->format('j'),
                    'in_month' => $date->format('Y-m') === $monthStart->format('Y-m'),
                    'today' => $today,
                    'offday' => ChecklistPeriod::isOffday($date),
                    'holiday' => $holidays[$dateStr]->description ?? null,
                    'status' => $status,
                    'editable' => $editable,
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
