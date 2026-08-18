<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('footer_note')->nullable();
            $table->boolean('collect_name')->default(false);
            $table->boolean('collect_phone')->default(false);
            $table->boolean('collect_email')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('compliance_questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('compliance_questionnaires')->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->text('question');
            $table->enum('answer_type', ['radio', 'text', 'textarea', 'date', 'email', 'phone', 'number', 'select', 'scale_5', 'scale_10'])->default('radio');
            $table->json('options')->nullable(); // for radio/select
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('questionnaire_id');
        });

        Schema::create('compliance_questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('compliance_questionnaires')->cascadeOnDelete();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_phone')->nullable();
            $table->string('respondent_email')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index('questionnaire_id');
        });

        Schema::create('compliance_questionnaire_response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('compliance_questionnaire_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('compliance_questionnaire_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();
            $table->index(['response_id', 'question_id'], 'qra_response_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_questionnaire_response_answers');
        Schema::dropIfExists('compliance_questionnaire_responses');
        Schema::dropIfExists('compliance_questionnaire_questions');
        Schema::dropIfExists('compliance_questionnaires');
    }
};
