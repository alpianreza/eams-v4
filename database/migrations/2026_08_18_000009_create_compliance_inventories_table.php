<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compliance Inventory (2E). FK strategy avoids destructive CASCADE on master data.
        Schema::create('compliance_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('asset_item_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_code')->unique();          // Q-020: business identifier, preserved exactly
            $table->string('type_description')->nullable();
            $table->string('specific_area')->nullable();      // Q-019: detail location (text, not a master relation)
            $table->string('pic')->nullable();                // legacy text column — kept ONLY for import/backward-compat (Q-007)
            $table->enum('status', ['good', 'need_repair', 'not_active'])->default('good'); // Q-017
            $table->unsignedInteger('qty')->default(1);
            $table->text('remark')->nullable();
            $table->date('expired_date')->nullable();         // Q-018: mainly APAR
            $table->string('photo')->nullable();
            $table->string('qr_image')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('asset_item_type_id');
            $table->index('area_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_inventories');
    }
};
