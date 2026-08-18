<?php

namespace App\Console\Commands;

use App\Services\Import\LegacyImporter;
use Illuminate\Console\Command;

/**
 * Legacy CI4 → Laravel data import (2L): `php artisan eams:import`.
 * Repeatable + idempotent, supports --dry-run, and prints an error report.
 * Reads the READ-ONLY `legacy` connection; never mutates the legacy DB.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'eams:import {--dry-run : Preview tanpa menulis ke database} {--only= : Batasi ke tabel tertentu (belum dipakai — impor berurutan)}';

    protected $description = 'Import data dari database CI4 legacy (READ-ONLY) ke database Laravel bersih.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'DRY-RUN: tidak ada penulisan.' : 'Menjalankan import legacy...');

        $report = (new LegacyImporter(dryRun: $dryRun))->run();

        $this->table(['Tabel', 'Dibaca', 'Ditulis', 'Error'], collect($report)->map(fn ($r, $k) => [
            $k, $r['read'], $r['written'], count($r['errors']),
        ])->values()->all());

        $errorCount = collect($report)->sum(fn ($r) => count($r['errors']));
        foreach ($report as $table => $r) {
            foreach ($r['errors'] as $err) {
                $this->warn("[{$table}] {$err}");
            }
        }

        $this->info($errorCount === 0 ? 'Import selesai tanpa error.' : "Import selesai dengan {$errorCount} error (lihat di atas).");

        return $errorCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
