<?php

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire\Questionnaire;
use App\Models\Questionnaire\QuestionnaireResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public questionnaire fill (CSRF-exempt via write-whitelist `kuesioner/*`; no login). */
class PublicQuestionnaireController extends Controller
{
    public function fill(Questionnaire $questionnaire): View|RedirectResponse
    {
        if (! $questionnaire->active) {
            return redirect()->route('home')->with('error', 'Kuesioner tidak tersedia.');
        }

        $questionnaire->load('questions');

        return view('questionnaire.public-fill', ['questionnaire' => $questionnaire]);
    }

    public function submit(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        abort_unless($questionnaire->active, 404);

        $questionnaire->load('questions');

        $rules = [];
        if ($questionnaire->collect_name) {
            $rules['respondent_name'] = ['required', 'string', 'max:120'];
        }
        if ($questionnaire->collect_phone) {
            $rules['respondent_phone'] = ['nullable', 'string', 'max:40'];
        }
        if ($questionnaire->collect_email) {
            $rules['respondent_email'] = ['nullable', 'email', 'max:120'];
        }
        foreach ($questionnaire->questions as $q) {
            $rules['q_'.$q->id] = $q->required ? ['required'] : ['nullable'];
        }
        $data = $request->validate($rules);

        $response = QuestionnaireResponse::create([
            'questionnaire_id' => $questionnaire->id,
            'respondent_name' => $data['respondent_name'] ?? null,
            'respondent_phone' => $data['respondent_phone'] ?? null,
            'respondent_email' => $data['respondent_email'] ?? null,
            'submitted_at' => now(),
        ]);

        foreach ($questionnaire->questions as $q) {
            $answer = $data['q_'.$q->id] ?? null;
            if ($answer === null || $answer === '') {
                continue;
            }
            $response->answers()->create(['question_id' => $q->id, 'answer' => is_array($answer) ? implode(', ', $answer) : (string) $answer]);
        }

        return redirect()->route('kuesioner.thanks', $questionnaire);
    }

    public function thanks(Questionnaire $questionnaire): View
    {
        return view('questionnaire.thanks', ['questionnaire' => $questionnaire]);
    }
}
