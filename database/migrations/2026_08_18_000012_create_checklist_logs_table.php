<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist results. Laravel adds updated_at (Q-023) vs legacy (created_at only).
        // checked_by: user_id + name snapshot (Q-006). status: ok|not_ok|na (Q-001).
        // FK strategy avoids the legacy destructive CASCADE on history (restrict, not cascade).
        Schema::create('checklist_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('compliance_inventories')->restrictOnDelete();
            $table->foreignId('asset_item_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('checklist_master_id')->constrained('checklist_master')->restrictOnDelete();
            $table->date('check_date');
            $table->string('period_key');
            $table->string('time_slot')->nullable();     // toilet 3-slot PG/SI/SO (BR-14)
            $table->enum('status', ['ok', 'not_ok', 'na']); // Q-001
            $table->text('remark')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('checked_by_name');            // Q-006 snapshot
            $table->enum('mode', ['standard', 'grid'])->default('standard'); // Q-016
            $table->enum('follow_up_status', ['open', 'monitoring', 'closed'])->nullable();
            $table->text('follow_up_note')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();                          // created_at + updated_at (Q-023)

            // BR-09: one log-set per inventory+period(+slot). App-enforced + index.
            $table->index(['inventory_id', 'period_key', 'time_slot']);
            $table->index(['period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_logs');
    }
};
