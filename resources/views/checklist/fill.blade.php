@extends('layouts.app')

@section('title', 'Checklist — ' . $inventory->asset_code)

@section('content')
<x-page-header
    variant="card"
    tone="checklist"
    eyebrow="Compliance"
    eyebrow-icon="bi-clipboard-check"
    :title="'Checklist — ' . $inventory->asset_code"
    :lead-html="($inventory->itemType->name ?? '—') . ' · Periode: <strong>' . $periodKey . '</strong> (' . $inventory->itemType->checklist_frequency . ')'"
/>

@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

@if(!$editable)
    <div class="alert alert-warning">Periode ini tidak dapat diisi (hari libur atau periode mendatang).</div>
@endif

<form method="POST" action="{{ route('compliance.checklist.store', $inventory) }}" enctype="multipart/form-data">
    @csrf
    <div class="card"><div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Pertanyaan</th><th style="width:280px">Hasil</th><th>Keterangan</th><th>Foto</th></tr></thead>
            <tbody>
            @forelse($questions as $q)
                <tr>
                    <td>{{ $q->question }}@if($q->require_photo)<span class="text-danger" title="wajib foto">*</span>@endif</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="status_{{ $q->id }}" id="ok_{{ $q->id }}" value="ok" @disabled(!$editable)>
                            <label class="btn btn-outline-success" for="ok_{{ $q->id }}">OK</label>

                            <input type="radio" class="btn-check" name="status_{{ $q->id }}" id="ng_{{ $q->id }}" value="not_ok" @disabled(!$editable)>
                            <label class="btn btn-outline-danger" for="ng_{{ $q->id }}">NOT OK</label>

                            @if($allowNa)
                            <input type="radio" class="btn-check" name="status_{{ $q->id }}" id="na_{{ $q->id }}" value="na" @disabled(!$editable)>
                            <label class="btn btn-outline-secondary" for="na_{{ $q->id }}">NA</label>
                            @endif
                        </div>
                    </td>
                    <td><input type="text" name="remark_{{ $q->id }}" class="form-control form-control-sm" @disabled(!$editable)></td>
                    <td><input type="file" name="photo_{{ $q->id }}" accept="image/*" class="form-control form-control-sm" @disabled(!$editable)></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pertanyaan untuk item type ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    @if($editable && $questions->isNotEmpty())
    <button type="submit" class="btn btn-primary mt-3">Simpan Checklist</button>
    @endif
</form>
@endsection
