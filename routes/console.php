<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('eams:about', function () {
    $this->info('EAMS — Laravel 13 rebuild (Milestone 1: foundation).');
})->purpose('Show EAMS rebuild info');
