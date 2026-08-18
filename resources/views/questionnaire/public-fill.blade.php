<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $questionnaire->title }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-4" style="max-width:720px">
<div class="card"><div class="card-body">
    <h1 class="h4">{{ $questionnaire->title }}</h1>
    @if($questionnaire->subtitle)<p class="text-muted">{{ $questionnaire->subtitle }}</p>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('kuesioner.submit', $questionnaire) }}">
        @csrf
        @if($questionnaire->collect_name)<div class="mb-3"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" name="respondent_name" class="form-control" required></div>@endif
        @if($questionnaire->collect_phone)<div class="mb-3"><label class="form-label">No. HP</label><input type="text" name="respondent_phone" class="form-control"></div>@endif
        @if($questionnaire->collect_email)<div class="mb-3"><label class="form-label">Email</label><input type="email" name="respondent_email" class="form-control"></div>@endif

        @foreach($questionnaire->questions as $q)
            <div class="mb-3">
                <label class="form-label">{{ $q->question }} @if($q->required)<span class="text-danger">*</span>@endif</label>
                @if($q->answer_type === 'textarea')
                    <textarea name="q_{{ $q->id }}" class="form-control" @required($q->required)></textarea>
                @elseif($q->hasOptions())
                    @foreach($q->options ?? [] as $opt)
                        <div class="form-check"><input class="form-check-input" type="radio" name="q_{{ $q->id }}" value="{{ $opt }}" id="q{{ $q->id }}_{{ $loop->index }}" @required($q->required)><label class="form-check-label" for="q{{ $q->id }}_{{ $loop->index }}">{{ $opt }}</label></div>
                    @endforeach
                @elseif(in_array($q->answer_type, ['scale_5','scale_10']))
                    @php($max = $q->answer_type === 'scale_5' ? 5 : 10)
                    <div class="d-flex gap-2 flex-wrap">@for($i=1;$i<=$max;$i++)<div class="form-check"><input class="form-check-input" type="radio" name="q_{{ $q->id }}" value="{{ $i }}" id="q{{ $q->id }}_s{{ $i }}" @required($q->required)><label class="form-check-label" for="q{{ $q->id }}_s{{ $i }}">{{ $i }}</label></div>@endfor</div>
                @else
                    <input type="{{ $q->answer_type === 'number' ? 'number' : ($q->answer_type === 'date' ? 'date' : ($q->answer_type === 'email' ? 'email' : 'text')) }}" name="q_{{ $q->id }}" class="form-control" @required($q->required)>
                @endif
            </div>
        @endforeach
        <button class="btn btn-primary">Kirim</button>
    </form>
    @if($questionnaire->footer_note)<p class="text-muted small mt-3 mb-0">{{ $questionnaire->footer_note }}</p>@endif
</div></div>
</div></body></html>
