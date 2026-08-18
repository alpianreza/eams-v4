@extends('layouts.app')

@section('title', $questionnaire->title)

@section('content')
<h1 class="h4 mb-1">{{ $questionnaire->title }}</h1>
<p class="text-muted small">Link publik: <code>/kuesioner/{{ $questionnaire->slug }}</code></p>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
        <h2 class="h6">Pertanyaan</h2>
        <ol class="mb-3">
            @foreach($questionnaire->questions as $q)
                <li>{{ $q->question }} <span class="badge bg-light text-dark">{{ $q->answer_type }}</span>@if($q->required) <span class="text-danger">*</span>@endif</li>
            @endforeach
        </ol>
        @can('manage-inventory')
        <form method="POST" action="{{ route('questionnaire.questions.store', $questionnaire) }}" class="border-top pt-3">
            @csrf
            <div class="mb-2"><label class="form-label small">Pertanyaan</label><input type="text" name="question" class="form-control form-control-sm" required></div>
            <div class="mb-2"><label class="form-label small">Tipe jawaban</label>
                <select name="answer_type" class="form-select form-select-sm">
                    @foreach(['radio','text','textarea','date','email','phone','number','select','scale_5','scale_10'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                </select></div>
            <div class="mb-2"><label class="form-label small">Opsi (satu per baris, untuk radio/select)</label><textarea name="options" class="form-control form-control-sm" rows="2"></textarea></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="required" value="1" id="req"><label class="form-check-label small" for="req">Wajib</label></div>
            <button class="btn btn-sm btn-primary">Tambah Pertanyaan</button>
        </form>
        @endcan
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
        <h2 class="h6">Respons Terbaru</h2>
        @forelse($responses as $r)
            <div class="border-bottom py-2"><strong>{{ $r->respondent_name ?? 'Anonim' }}</strong> <small class="text-muted">{{ $r->submitted_at?->format('Y-m-d H:i') }}</small></div>
        @empty
            <div class="text-muted">Belum ada respons.</div>
        @endforelse
        {{ $responses->links() }}
    </div></div>
  </div>
</div>
@endsection
