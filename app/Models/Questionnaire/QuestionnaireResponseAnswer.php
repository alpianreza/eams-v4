<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireResponseAnswer extends Model
{
    protected $table = 'compliance_questionnaire_response_answers';

    protected $fillable = ['response_id', 'question_id', 'answer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'question_id');
    }
}
