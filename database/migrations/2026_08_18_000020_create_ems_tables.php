<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EMS/GHG: 4 categories, each a year-meta table + a monthly per-section entry table.
        foreach (['water_consumption', 'electric_consumption', 'stationary_combustion', 'mobile_combustion'] as $cat) {
            Schema::create("ems_{$cat}_years", function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('report_year')->unique();
                $table->decimal('production_output', 14, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            Schema::create("ems_{$cat}_entries", function (Blueprint $table) use ($cat) {
                $table->id();
                $table->unsignedSmallInteger('report_year');
                $table->string('section_key');
                $table->unsignedTinyInteger('report_month'); // 1-12
                $table->decimal('consumption_amount', 14, 3)->nullable();
                $table->timestamps();
                $table->unique(['report_year', 'section_key', 'report_month'], "{$cat}_uniq"); // 1 entry per section+month+year
            });
        }
    }

    public function down(): void
    {
        foreach (['water_consumption', 'electric_consumption', 'stationary_combustion', 'mobile_combustion'] as $cat) {
            Schema::dropIfExists("ems_{$cat}_entries");
            Schema::dropIfExists("ems_{$cat}_years");
        }
    }
};
