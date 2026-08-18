<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist question definitions per item type (LIVE table — NOT the dead compliance_checklist_* family).
        Schema::create('checklist_master', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_item_type_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->boolean('require_photo')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['asset_item_type_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_master');
    }
};
