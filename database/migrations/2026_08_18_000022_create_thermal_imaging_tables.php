<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thermal_imaging_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('section')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('thermal_imaging_reports', function (Blueprint $table) {
            $table->id();
            $table->date('inspection_date');
            $table->string('inspector_name')->nullable();
            $table->string('facility')->nullable();
            $table->string('area_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('thermal_imaging_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('thermal_imaging_reports')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('thermal_imaging_locations')->nullOnDelete();
            $table->string('location_name')->nullable();
            $table->decimal('celsius', 6, 2)->nullable();
            $table->string('thermal_image')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thermal_imaging_report_items');
        Schema::dropIfExists('thermal_imaging_reports');
        Schema::dropIfExists('thermal_imaging_locations');
    }
};
