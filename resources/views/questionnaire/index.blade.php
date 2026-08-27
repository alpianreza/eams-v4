@extends('layouts.app')

@section('title', 'Kuesioner')

@section('content')
<x-page-header
    variant="card"
    tone="questionnaire"
    eyebrow="Compliance"
    eyebrow-icon="bi-ui-checks"
    title="Pusat Kuesioner"
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

@can('manage-inventory')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('questionnaire.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-4"><label class="form-label small">Judul</label><input type="text" name="title" class="form-control form-control-sm" required></div>
        <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" name="collect_name" value="1" id="cn"><label class="form-check-label small" for="cn">Kumpulkan nama</label></div></div>
        <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" name="collect_email" value="1" id="ce"><label class="form-check-label small" for="ce">Email</label></div></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Buat</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Judul</th><th>Pertanyaan</th><th>Respons</th><th>Status</th><th class="text-end">Link Publik</th></tr></thead>
        <tbody>
        @forelse($questionnaires as $q)
            <tr>
                <td><a href="{{ route('questionnaire.show', $q) }}">{{ $q->title }}</a></td>
                <td>{{ $q->questions_count }}</td>
                <td>{{ $q->responses_count }}</td>
                <td><span class="badge bg-{{ $q->active ? 'success' : 'secondary' }}">{{ $q->active ? 'aktif' : 'nonaktif' }}</span></td>
                <td class="text-end"><code class="small">/kuesioner/{{ $q->slug }}</code></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada kuesioner.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
