<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist change audit trail (Q-023 — Laravel improvement, NOT legacy behavior).
        Schema::create('checklist_log_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_log_id')->constrained('checklist_logs')->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('old_remark')->nullable();
            $table->text('new_remark')->nullable();
            $table->string('old_photo')->nullable();
            $table->string('new_photo')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('checklist_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_log_histories');
    }
};
