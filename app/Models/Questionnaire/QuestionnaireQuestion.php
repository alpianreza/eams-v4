<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireQuestion extends Model
{
    protected $table = 'compliance_questionnaire_questions';

    protected $fillable = ['questionnaire_id', 'section', 'question', 'answer_type', 'options', 'required', 'sort_order'];

    protected function casts(): array
    {
        return ['options' => 'array', 'required' => 'boolean'];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

    public function hasOptions(): bool
    {
        return in_array($this->answer_type, ['radio', 'select'], true);
    }
}
