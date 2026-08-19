<?php

namespace App\Http\Controllers\Ranking;

use App\Http\Controllers\Controller;
use App\Models\ChecklistLog;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** PIC leaderboard (BR-18): score = ontime×10 + late×3, per checker (checked_by_user_id + name). */
class RankingController extends Controller
{
    public function index(): View
    {
        $rows = ChecklistLog::query()->whereNotNull('checked_by_user_id')->get();

        $board = [];
        foreach ($rows as $log) {
            $key = $log->checked_by_user_id;
            $board[$key] ??= ['name' => $log->checked_by_name ?? '—', 'ontime' => 0, 'late' => 0, 'score' => 0];

            if ($this->isOnTime($log)) {
                $board[$key]['ontime']++;
                $board[$key]['score'] += 10;
            } else {
                $board[$key]['late']++;
                $board[$key]['score'] += 3;
            }
        }

        $board = collect($board)->sortByDesc('score')->values()->all();

        return view('ranking.index', ['board' => $board]);
    }

    /** BR-18: on-time if check_date ≤ the period's end (per its frequency). */
    protected function isOnTime(ChecklistLog $log): bool
    {
        $frequency = $log->itemType->checklist_frequency ?? 'daily';
        $periodDate = Carbon::parse(substr((string) $log->period_key, 0, 10));
        [, $end] = ChecklistPeriod::bounds($frequency, $periodDate);

        return Carbon::parse($log->check_date)->lte($end);
    }
}
