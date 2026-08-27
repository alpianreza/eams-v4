<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Key-value application settings (Settings module). Sensitive values are encrypted at rest.
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
