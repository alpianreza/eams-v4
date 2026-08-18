<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->boolean('all_day')->default(true);
            $table->string('color')->nullable();
            $table->string('sticker')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('start_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_calendar_events');
    }
};
