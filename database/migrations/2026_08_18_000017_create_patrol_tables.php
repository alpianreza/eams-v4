<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrol_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('patrol_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('area')->nullable();
            $table->string('barcode_value')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('radius_m')->default(10);
            $table->decimal('map_x', 5, 2)->nullable();
            $table->decimal('map_y', 5, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('patrol_route_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_route_id')->constrained('patrol_routes')->cascadeOnDelete();
            $table->foreignId('patrol_checkpoint_id')->constrained('patrol_checkpoints')->cascadeOnDelete();
            $table->unsignedInteger('route_order')->default(1);
            $table->timestamps();
            $table->unique(['patrol_route_id', 'patrol_checkpoint_id'], 'route_checkpoint_unique');
        });

        Schema::create('patrol_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_route_id')->constrained('patrol_routes')->cascadeOnDelete();
            $table->date('patrol_date');
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->unsignedInteger('total_checkpoints')->default(0);
            $table->unsignedInteger('checked_count')->default(0);
            $table->unsignedInteger('issue_count')->default(0);
            $table->timestamps();
            $table->index(['patrol_route_id', 'patrol_date']);
        });

        Schema::create('patrol_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_session_id')->constrained('patrol_sessions')->cascadeOnDelete();
            $table->foreignId('patrol_route_id')->constrained('patrol_routes')->cascadeOnDelete();
            $table->foreignId('patrol_checkpoint_id')->constrained('patrol_checkpoints')->cascadeOnDelete();
            $table->foreignId('checked_by')->constrained('users')->cascadeOnDelete();
            $table->string('barcode_value')->nullable();
            $table->enum('status', ['ok', 'issue'])->default('ok');
            $table->text('note')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('distance_m', 6, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->index(['patrol_session_id', 'patrol_checkpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_logs');
        Schema::dropIfExists('patrol_sessions');
        Schema::dropIfExists('patrol_route_checkpoints');
        Schema::dropIfExists('patrol_checkpoints');
        Schema::dropIfExists('patrol_routes');
    }
};
