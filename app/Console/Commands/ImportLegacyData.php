<?php

namespace App\Console\Commands;

use App\Services\Import\LegacyImporter;
use Illuminate\Console\Command;

/**
 * Legacy CI4 → Laravel import. Source is read-only; target changes are
 * transactional and rolled back for dry-runs or validation errors.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'eams:import {--dry-run : Validasi lengkap lalu rollback seluruh perubahan} {--only= : Batasi ke tabel tertentu (belum dipakai — impor berurutan)}';

    protected $description = 'Import data dari database CI4 legacy (READ-ONLY) ke database Laravel bersih.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'DRY-RUN: menjalankan validasi lengkap; semua perubahan akan di-rollback.' : 'Menjalankan import legacy...');

        $importer = new LegacyImporter(dryRun: $dryRun);

        try {
            $report = $importer->run();
        } catch (\Throwable $e) {
            $this->error('Import gagal dan seluruh transaksi telah di-rollback.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Tabel', 'Dibaca', 'Ditulis', 'Error'], collect($report)->map(fn ($result, $table) => [
            $table, $result['read'], $result['written'], count($result['errors']),
        ])->values()->all());

        $errorCount = collect($report)->sum(fn ($result) => count($result['errors']));
        foreach ($report as $table => $result) {
            foreach ($result['errors'] as $error) {
                $this->warn("[{$table}] {$error}");
            }
        }

        if ($dryRun && $errorCount === 0) {
            $this->info('DRY-RUN berhasil tanpa error; seluruh perubahan simulasi sudah di-rollback.');
        } elseif ($errorCount > 0) {
            $this->error("Import menemukan {$errorCount} error; tidak ada perubahan yang disimpan (rollback penuh).");
        } else {
            $this->info('Import selesai tanpa error dan transaksi berhasil disimpan.');
        }

        return $errorCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
