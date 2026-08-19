<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('eams:about', function () {
    $this->info('EAMS — Laravel 13 rebuild (foundation + semua modul + UI).');
})->purpose('Show EAMS rebuild info');

/*
| Laravel Scheduler — menggantikan Windows schtasks legacy (§15).
*/
// BR-39: backup harian (full) + pangkas yang lebih tua dari retensi.
Schedule::command('eams:backup', ['full', '--prune' => true])->dailyAt('01:00');
// BR-23/24: reminder checklist mingguan ke PIC (hari libur dihormati di dalam command).
Schedule::command('eams:remind-checklists')->weeklyOn(1, '08:00');
// Q-012: status online/offline perangkat (threshold terpusat, default 600 dtk).
Schedule::command('eams:device-status-check')->everyMinute();
