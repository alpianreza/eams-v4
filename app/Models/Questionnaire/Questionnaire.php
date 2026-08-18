<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    protected $table = 'compliance_questionnaires';

    protected $fillable = ['slug', 'title', 'subtitle', 'description', 'footer_note', 'collect_name', 'collect_phone', 'collect_email', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['collect_name' => 'boolean', 'collect_phone' => 'boolean', 'collect_email' => 'boolean', 'active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionnaireQuestion::class, 'questionnaire_id')->orderBy('sort_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionnaireResponse::class, 'questionnaire_id');
    }
}
