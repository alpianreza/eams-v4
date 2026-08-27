<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_logs', function (Blueprint $table) {
            // Stable source identity makes legacy imports repeatable without deleting
            // application-created logs or their correction history.
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id');
            $table->unique('legacy_id', 'checklist_logs_legacy_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_logs', function (Blueprint $table) {
            $table->dropUnique('checklist_logs_legacy_id_unique');
            $table->dropColumn('legacy_id');
        });
    }
};
