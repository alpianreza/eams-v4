<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit trail (BR-40). created_at only — no updated_at (matches production).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('status', 20)->default('success');
            $table->string('login_method', 30)->nullable();
            $table->string('channel', 30)->nullable();
            $table->string('route')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
