<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks login sessions for idle-expiry (8h) + auth audit (BR-40).
        Schema::create('login_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username')->nullable();
            $table->string('login_method', 30)->default('password');
            $table->string('channel', 30)->default('web');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('last_route')->nullable();
            $table->string('logout_reason', 50)->nullable();
            $table->boolean('is_active')->default(true);

            $table->index('user_id');
            $table->index('started_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_sessions');
    }
};
