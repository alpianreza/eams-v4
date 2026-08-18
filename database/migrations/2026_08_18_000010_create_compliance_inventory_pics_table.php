<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PIC source of truth (Q-007): max 2 (app-enforced), equal, NO is_primary.
        Schema::create('compliance_inventory_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['compliance_inventory_id', 'user_id'], 'inventory_pic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_inventory_pics');
    }
};
