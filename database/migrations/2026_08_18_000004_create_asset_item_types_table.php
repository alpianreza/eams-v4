<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Business identifier = `code` (Q-015) — behavior must never depend on the auto-increment id.
        Schema::create('asset_item_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique(); // business identifier (Q-015)
            $table->enum('checklist_frequency', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->boolean('allow_na')->default(false); // NA allowed as a valid result (Q-001)
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_item_types');
    }
};
