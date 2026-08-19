<?php

namespace App\Console\Commands;

use App\Services\BackupManager;
use Illuminate\Console\Command;

/** Daily backup (BR-39) — run via the Laravel Scheduler (replaces Windows schtasks). */
class CreateBackup extends Command
{
    protected $signature = 'eams:backup {type=database : database|files|full} {--prune : Prune backups older than retention}';

    protected $description = 'Buat backup (BR-39) + pangkas yang lebih tua dari retensi.';

    public function handle(BackupManager $backups): int
    {
        $type = (string) $this->argument('type');
        $backup = $backups->create($type, null);
        $this->info("Backup dibuat: {$backup->filename} ({$backup->size_bytes} bytes)");

        if ($this->option('prune')) {
            $this->info('Dipangkas: '.$backups->prune().' backup lama.');
        }

        return self::SUCCESS;
    }
}
