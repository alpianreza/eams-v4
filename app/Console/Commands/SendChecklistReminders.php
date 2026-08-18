<?php

namespace App\Console\Commands;

use App\Services\Checklist\WeeklyChecklistReminder;
use Illuminate\Console\Command;

/** Weekly checklist reminder (BR-23/24) — schedule weekly on a working day. */
class SendChecklistReminders extends Command
{
    protected $signature = 'eams:remind-checklists';

    protected $description = 'Kirim pengingat checklist pending ke PIC (hormati hari libur; hanya periode due).';

    public function handle(WeeklyChecklistReminder $reminder): int
    {
        $sent = $reminder->send();
        $this->info("Checklist reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
