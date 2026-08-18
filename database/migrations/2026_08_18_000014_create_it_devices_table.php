<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IT devices monitored by the EAMS Agent (BR-35/36). asset_id links to the IT Asset
        // module (2K) — kept as a plain nullable column until that module lands.
        Schema::create('it_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->nullable()->index();
            $table->string('hostname')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('bios')->nullable();
            $table->string('device_user')->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('cpu_name')->nullable();
            $table->string('cpu_core')->nullable();
            $table->string('cpu_thread')->nullable();
            $table->string('gpu')->nullable();
            $table->string('disk_model')->nullable();
            $table->string('architecture')->nullable();
            $table->unsignedInteger('ram_gb')->nullable();
            $table->unsignedInteger('storage_gb')->nullable();
            $table->string('last_ip')->nullable();
            $table->string('mac_address')->nullable()->index();
            $table->string('agent_version')->nullable();
            $table->timestamp('last_update_check')->nullable();
            $table->timestamp('last_seen')->nullable()->index();
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->string('device_token')->unique();
            $table->text('cpu')->nullable(); // JSON "extra" blob (diagnostics/hardware/session)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_devices');
    }
};
