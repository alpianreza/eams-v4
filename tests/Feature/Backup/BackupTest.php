<?php

namespace Tests\Feature\Backup;

use App\Models\Backup;
use App\Services\BackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_is_created_with_record_and_file(): void
    {
        Storage::fake('local');

        $backup = app(BackupManager::class)->create('database');

        $this->assertDatabaseHas('backups', ['type' => 'database', 'status' => 'done']);
        $this->assertStringStartsWith('backup-database-', $backup->filename); // BR-39 naming
        Storage::disk('local')->assertExists($backup->path);
    }

    public function test_prune_removes_backups_older_than_retention(): void
    {
        Storage::fake('local');
        config()->set('eams.backup_retention_days', 30);

        app(BackupManager::class)->create('database');
        Backup::first()->update(['created_at' => now()->subDays(40)]); // older than 30-day retention
        app(BackupManager::class)->create('database'); // a fresh one

        $removed = app(BackupManager::class)->prune();

        $this->assertSame(1, $removed);       // the old one pruned
        $this->assertSame(1, Backup::count()); // the fresh one kept
    }
}
