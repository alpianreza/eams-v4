<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Queued remote commands for agents (BR-35/36).
        Schema::create('it_device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('it_devices')->cascadeOnDelete();
            $table->string('command_id')->index();
            $table->string('command');
            $table->json('payload_json')->nullable();
            $table->enum('status', ['queued', 'dispatched', 'success', 'error'])->default('queued');
            $table->text('result')->nullable();
            $table->string('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_device_commands');
    }
};
