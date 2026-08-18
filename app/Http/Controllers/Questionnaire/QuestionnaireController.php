<?php

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire\Questionnaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Admin questionnaire management (Compliance). */
class QuestionnaireController extends Controller
{
    public function index(): View
    {
        return view('questionnaire.index', [
            'questionnaires' => Questionnaire::withCount(['questions', 'responses'])->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'collect_name' => ['sometimes', 'boolean'],
            'collect_phone' => ['sometimes', 'boolean'],
            'collect_email' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['title']);

        Questionnaire::create($data);

        return back()->with('status', 'Kuesioner dibuat.');
    }

    public function show(Questionnaire $questionnaire): View
    {
        $questionnaire->load('questions');
        $responses = $questionnaire->responses()->with('answers')->latest('submitted_at')->paginate(20);

        return view('questionnaire.show', ['questionnaire' => $questionnaire, 'responses' => $responses]);
    }

    public function addQuestion(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
            'answer_type' => ['required', 'in:radio,text,textarea,date,email,phone,number,select,scale_5,scale_10'],
            'options' => ['nullable', 'string'],
            'required' => ['sometimes', 'boolean'],
        ]);

        $data['options'] = $this->parseOptions($data['options'] ?? null);
        $data['sort_order'] = (int) $questionnaire->questions()->max('sort_order') + 1;

        $questionnaire->questions()->create($data);

        return back()->with('status', 'Pertanyaan ditambahkan.');
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'kuesioner';
        $slug = $base;
        $i = 2;
        while (Questionnaire::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function parseOptions(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));

        return $lines === [] ? null : $lines;
    }
}
