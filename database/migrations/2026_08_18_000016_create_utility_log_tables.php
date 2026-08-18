<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Utility (Boiler & Utility) daily logs — legacy-compatible table names (BR-29/30).
        Schema::create('boiler_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->index();
            $table->time('log_time')->nullable();
            $table->unsignedInteger('polybag')->nullable();
            $table->decimal('kg', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pdam_water_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->index();
            $table->time('log_time')->nullable();
            $table->decimal('meter_reading', 12, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        // 1 data per day (legacy unique log_date).
        Schema::create('pdam_water_boiler_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->unique();
            $table->time('log_time')->nullable();
            $table->decimal('meter_reading', 12, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('ipal_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->index();
            $table->time('log_time')->nullable();
            $table->decimal('value', 12, 2)->nullable(); // pembacaan harian IPAL (limbah)
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boiler_fuel_logs');
        Schema::dropIfExists('pdam_water_logs');
        Schema::dropIfExists('pdam_water_boiler_logs');
        Schema::dropIfExists('ipal_logs');
    }
};
