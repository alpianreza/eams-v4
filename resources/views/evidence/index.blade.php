@extends('layouts.app')

@section('title', 'Evidence Center')

@section('content')
<h1 class="h4 mb-3">Evidence Center — Temuan NOT_OK</h1>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

<form method="GET" class="d-flex gap-2 mb-3">
    <select name="follow_up_status" class="form-select form-select-sm w-auto">
        <option value="">Semua follow-up</option>
        @foreach(['open','monitoring','closed'] as $s)<option value="{{ $s }}" @selected($status===$s)>{{ $s }}</option>@endforeach
    </select>
    <button class="btn btn-sm btn-outline-secondary">Filter</button>
</form>

<div class="card"><div class="card-body p-0">
    @forelse($findings as $f)
        <div class="border-bottom p-3">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <code>{{ $f->inventory->asset_code ?? '—' }}</code> <strong>{{ $f->inventory->itemType->name ?? '' }}</strong>
                    <span class="badge bg-danger">not_ok</span>
                    <div class="text-muted small">{{ $f->question->question ?? '' }} · {{ $f->check_date }} · oleh {{ $f->checked_by_name }}</div>
                    @if($f->remark)<div class="small mt-1">{{ $f->remark }}</div>@endif
                </div>
                <form method="POST" action="{{ route('evidence.followup', $f) }}" class="d-flex gap-2 align-items-center">
                    @csrf @method('PUT')
                    <select name="follow_up_status" class="form-select form-select-sm">
                        @foreach(['open','monitoring','closed'] as $s)<option value="{{ $s }}" @selected($f->follow_up_status===$s)>{{ $s }}</option>@endforeach
                    </select>
                    <input type="text" name="follow_up_note" value="{{ $f->follow_up_note }}" placeholder="catatan" class="form-control form-control-sm">
                    <input type="date" name="follow_up_date" value="{{ $f->follow_up_date }}" class="form-control form-control-sm">
                    @can('write')<button class="btn btn-sm btn-primary">Simpan</button>@endcan
                </form>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">Tidak ada temuan.</div>
    @endforelse
</div></div>
{{ $findings->links() }}
@endsection
