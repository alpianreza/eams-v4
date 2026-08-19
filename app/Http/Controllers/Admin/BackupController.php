<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Admin: backups (BR-39) — list + trigger + prune. */
class BackupController extends Controller
{
    public function index(): View
    {
        return view('admin.backups.index', [
            'backups' => Backup::latest('id')->paginate(20),
            'retentionDays' => (int) config('eams.backup_retention_days', 30),
        ]);
    }

    public function store(BackupManager $backups): RedirectResponse
    {
        $backup = $backups->create('database');

        return back()->with('status', 'Backup dibuat: '.$backup->filename);
    }

    public function prune(BackupManager $backups): RedirectResponse
    {
        $removed = $backups->prune();

        return back()->with('status', "{$removed} backup lama dipangkas.");
    }
}
