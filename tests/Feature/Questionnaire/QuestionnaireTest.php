<?php

namespace Tests\Feature\Questionnaire;

use App\Models\Questionnaire\Questionnaire;
use App\Models\Questionnaire\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    protected function makeQuestionnaire(): Questionnaire
    {
        $q = Questionnaire::create(['slug' => 'survei-kepuasan', 'title' => 'Survei Kepuasan', 'collect_name' => true, 'active' => true]);
        $q->questions()->create(['question' => 'Seberapa puas Anda?', 'answer_type' => 'scale_5', 'required' => true, 'sort_order' => 1]);
        $q->questions()->create(['question' => 'Saran', 'answer_type' => 'textarea', 'required' => false, 'sort_order' => 2]);

        return $q;
    }

    public function test_admin_can_create_questionnaire_with_unique_slug(): void
    {
        $this->actingAs($this->admin())->post(route('questionnaire.store'), ['title' => 'Survei Baru', 'collect_name' => '1'])->assertRedirect();
        $this->assertDatabaseHas('compliance_questionnaires', ['title' => 'Survei Baru', 'slug' => 'survei-baru']);

        // second with same title gets a unique slug
        $this->actingAs($this->admin())->post(route('questionnaire.store'), ['title' => 'Survei Baru']);
        $this->assertDatabaseHas('compliance_questionnaires', ['slug' => 'survei-baru-2']);
    }

    public function test_public_fill_form_is_reachable_by_guest(): void
    {
        $q = $this->makeQuestionnaire();

        // public route — no login required
        $this->get(route('kuesioner.fill', $q))->assertOk()->assertSee('Survei Kepuasan');
    }

    public function test_public_submit_stores_response_and_answers(): void
    {
        $q = $this->makeQuestionnaire();
        $question = $q->questions()->where('answer_type', 'scale_5')->first();

        // guest (no login) submits — write-whitelisted `kuesioner/*`
        $this->post(route('kuesioner.submit', $q), [
            'respondent_name' => 'Tamu',
            'q_'.$question->id => '5',
        ])->assertRedirect(route('kuesioner.thanks', $q));

        $this->assertDatabaseHas('compliance_questionnaire_responses', ['questionnaire_id' => $q->id, 'respondent_name' => 'Tamu']);
        $this->assertDatabaseHas('compliance_questionnaire_response_answers', ['question_id' => $question->id, 'answer' => '5']);
    }

    public function test_required_question_is_enforced_on_public_submit(): void
    {
        $q = $this->makeQuestionnaire();

        $this->post(route('kuesioner.submit', $q), ['respondent_name' => 'Tamu'])
            ->assertSessionHasErrors('q_'.$q->questions()->where('required', true)->first()->id);
    }

    public function test_inactive_questionnaire_is_not_fillable(): void
    {
        $q = $this->makeQuestionnaire();
        $q->update(['active' => false]);

        $this->post(route('kuesioner.submit', $q), ['respondent_name' => 'Tamu'])->assertNotFound();
    }

    public function test_answer_types_enum_is_supported(): void
    {
        $q = $this->makeQuestionnaire();
        $this->assertContains($q->questions()->first()->answer_type, ['radio', 'text', 'textarea', 'date', 'email', 'phone', 'number', 'select', 'scale_5', 'scale_10']);
    }
}
