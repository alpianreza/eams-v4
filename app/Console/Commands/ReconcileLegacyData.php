<?php

namespace App\Console\Commands;

use App\Services\Import\LegacyReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Formatter\OutputFormatter;

class ReconcileLegacyData extends Command
{
    protected $signature = 'eams:reconcile
                            {--samples=5 : Jumlah contoh baris yang ditampilkan per temuan}
                            {--json : Cetak laporan mentah sebagai JSON}
                            {--save= : Simpan laporan JSON ke storage/app/<nama-file>}';

    protected $description = 'Bandingkan database legacy dengan database Laravel dan laporkan selisih data (read-only).';

    public function handle(LegacyReconciler $reconciler): int
    {
        $report = $reconciler->reconcile(['sampleLimit' => (int) $this->option('samples')]);
        $payload = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($file = $this->option('save')) {
            Storage::disk('local')->put($file, (string) $payload);
            $this->line('Laporan disimpan di storage/app/'.$file);
        }

        if ($this->option('json')) {
            $this->output->writeln((string) $payload);
        } else {
            $this->renderReport($report);
        }

        return $report['ok'] ? self::SUCCESS : self::FAILURE;
    }

    protected function renderReport(array $report): void
    {
        $this->newLine();
        $this->line('<options=bold>EAMS - Rekonsiliasi Data Legacy</>');
        $this->line('Dibuat    : '.$report['generated_at']);
        $this->line('DB legacy : '.$this->safe($report['legacy_database'] ?: '-'));

        if (! $report['legacy_available']) {
            $this->newLine();
            $this->error('Koneksi legacy gagal: '.$this->safe($report['legacy_error'] ?? 'unknown error'));
            $this->line('Isi LEGACY_DB_* di .env lalu jalankan ulang perintah ini.');
        }

        if ($report['row_counts'] !== []) {
            $this->newLine();
            $this->line('<options=bold>1. Jumlah baris</>');
            $this->table(
                ['Tabel legacy', 'Tabel target', 'Baris legacy', 'Kunci unik legacy', 'Baris target', 'Delta'],
                array_map(fn (array $row): array => [
                    $row['legacy_table'],
                    $row['target_table'],
                    $row['legacy_rows'] ?? '-',
                    $row['legacy_unique_keys'] ?? '-',
                    $row['target_rows'],
                    $row['delta'] === null ? '-' : sprintf('%+d', $row['delta']),
                ], $report['row_counts'])
            );
            $this->line('  Delta positif berarti ada data yang dibuat langsung di aplikasi (bukan temuan).');
        }

        $this->renderFindings('2. Nilai legacy yang akan dinormalisasi importer', $report['legacy_normalizations'], function (array $row): string {
            $samples = implode(', ', array_map(
                fn (array $sample): string => '#'.$sample['id'].'="'.$this->safe((string) $sample['value']).'"',
                $row['samples']
            ));

            return sprintf(
                '%s.%s: %d baris dipaksa menjadi "%s" [contoh: %s]',
                $row['table'],
                $row['column'],
                $row['count'],
                $row['normalized_to'],
                $samples
            );
        });

        $this->renderFindings('3. Relasi legacy yang menggantung', $report['legacy_orphans'], fn (array $row): string => sprintf(
            '%s.%s -> %s: %d baris [id: %s]',
            $row['table'],
            $row['column'],
            $row['references'],
            $row['count'],
            implode(', ', $row['sample_ids'])
        ));

        $this->renderFindings('4. Kunci bisnis ganda di legacy', $report['legacy_duplicates'], function (array $row): string {
            return sprintf(
                '%s.%s: %d nilai ganda [contoh: %s]',
                $row['table'],
                $row['column'],
                $row['groups'],
                $this->safe(implode(', ', array_map('strval', $row['sample_values'])))
            );
        });

        $this->renderFindings('5. Relasi target yang menggantung', $report['target_orphans'], fn (array $row): string => sprintf(
            '%s.%s -> %s: %d baris [id: %s]',
            $row['table'],
            $row['column'],
            $row['references'],
            $row['count'],
            implode(', ', $row['sample_ids'])
        ));

        $this->renderFindings('6. Duplikat & enum tidak valid di target', array_merge($report['target_duplicates'], $report['target_invalid_enums']), function (array $row): string {
            if (isset($row['groups'])) {
                return sprintf(
                    'duplikat %s.%s: %d nilai [contoh: %s]',
                    $row['table'],
                    $row['column'],
                    $row['groups'],
                    $this->safe(implode(', ', array_map('strval', $row['sample_values'])))
                );
            }

            $samples = implode(', ', array_map(
                fn (array $sample): string => '#'.$sample['id'].'="'.$this->safe((string) $sample['value']).'"',
                $row['samples']
            ));

            return sprintf(
                'enum %s.%s: %d baris di luar [%s] [contoh: %s]',
                $row['table'],
                $row['column'],
                $row['count'],
                implode(', ', $row['allowed']),
                $samples
            );
        });

        if ($parity = $report['checklist_log_parity']) {
            $this->newLine();
            $this->line('<options=bold>7. Parity checklist_logs (berdasarkan legacy_id)</>');
            $this->table(['Metrik', 'Nilai'], [
                ['Baris legacy', $parity['legacy_rows']],
                ['Baris target hasil import', $parity['imported_rows']],
                ['Baris target dibuat di aplikasi', $parity['app_created_rows']],
                ['Belum ada di target', $parity['missing_in_target']],
                ['Ada di target tapi hilang di legacy', $parity['extra_in_target']],
                ['Tanggal tidak bisa diturunkan', $parity['unresolvable_dates']],
            ]);

            if ($parity['missing_sample_legacy_ids'] !== []) {
                $this->line('  Contoh legacy_id yang belum masuk: '.implode(', ', $parity['missing_sample_legacy_ids']));
            }
            if ($parity['extra_sample_legacy_ids'] !== []) {
                $this->line('  Contoh legacy_id yang sudah hilang di legacy: '.implode(', ', $parity['extra_sample_legacy_ids']));
            }
            foreach ($parity['unresolvable_date_samples'] as $sample) {
                $this->line(sprintf(
                    '  log#%s check_date="%s" period_key="%s"',
                    $sample['id'],
                    $this->safe((string) $sample['check_date']),
                    $this->safe((string) $sample['period_key'])
                ));
            }
        }

        $this->newLine();
        if ($report['ok']) {
            $this->info('Semua pemeriksaan parity lolos.');

            return;
        }

        $this->warn(count($report['issues']).' temuan:');
        foreach ($report['issues'] as $issue) {
            $this->line('  - '.$this->safe($issue));
        }
        $this->newLine();
        $this->line('Exit code 1 karena masih ada temuan yang perlu ditindak.');
    }

    protected function renderFindings(string $title, array $findings, callable $formatter): void
    {
        $this->newLine();
        $this->line('<options=bold>'.$title.'</>');

        if ($findings === []) {
            $this->line('  <fg=green>OK</> tidak ada temuan');

            return;
        }

        foreach ($findings as $finding) {
            $this->line('  <fg=yellow>-</> '.$formatter($finding));
        }
    }

    protected function safe(?string $value): string
    {
        return OutputFormatter::escape((string) $value);
    }
}
