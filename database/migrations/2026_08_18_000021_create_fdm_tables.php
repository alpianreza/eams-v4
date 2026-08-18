<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fdm_production_section_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('report_year')->unique();
            $table->timestamps();
        });

        Schema::create('fdm_production_section_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('fdm_production_section_years')->cascadeOnDelete();
            $table->string('section_key');
            $table->string('section_label')->nullable();
            $table->string('entry_type')->nullable();
            $table->string('frequency_label')->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->json('monthly_values')->nullable(); // {1: val, ..., 12: val}
            $table->timestamps();
            $table->index(['year_id', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fdm_production_section_entries');
        Schema::dropIfExists('fdm_production_section_years');
    }
};
