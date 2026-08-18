<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireResponse extends Model
{
    protected $table = 'compliance_questionnaire_responses';

    protected $fillable = ['questionnaire_id', 'respondent_name', 'respondent_phone', 'respondent_email', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuestionnaireResponseAnswer::class, 'response_id');
    }
}
