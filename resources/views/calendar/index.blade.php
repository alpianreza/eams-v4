@extends('layouts.app')

@section('title', 'Kalender Compliance')

@section('content')
<x-page-header
    variant="card"
    tone="soft"
    eyebrow="Compliance"
    eyebrow-icon="bi-calendar3"
    title="Kalender Compliance"
>
    <x-slot:actions>
        <form method="GET" class="d-flex gap-2"><input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-secondary">Tampilkan</button></form>
    </x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('calendar.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3"><label class="form-label small">Judul event</label><input type="text" name="title" class="form-control form-control-sm" required></div>
        <div class="col-auto"><label class="form-label small">Mulai</label><input type="date" name="start_at" class="form-control form-control-sm" required></div>
        <div class="col-auto"><label class="form-label small">Selesai</label><input type="date" name="end_at" class="form-control form-control-sm"></div>
        <div class="col-auto"><label class="form-label small">Warna</label><input type="color" name="color" class="form-control form-control-color form-control-sm" value="#0d6efd"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Tambah</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-bordered mb-0 text-center align-top" style="table-layout:fixed">
        <thead><tr>@foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)<th class="small">{{ $d }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($weeks as $week)
            <tr style="height:96px">
            @foreach($week as $day)
                <td class="text-start align-top p-1 {{ $day['offday'] ? 'table-secondary' : '' }} {{ $day['in_month'] ? '' : 'text-muted bg-light' }}" style="vertical-align:top">
                    <div class="small fw-bold">{{ $day['day'] }}</div>
                    @if($day['holiday'])<div class="badge bg-danger text-wrap" style="font-size:0.65rem">{{ $day['holiday'] }}</div>@endif
                    @foreach($day['events'] as $ev)
                        <div class="badge text-wrap w-100 text-start" style="background:{{ $ev->color ?: '#0d6efd' }};font-size:0.65rem">{{ $ev->sticker }} {{ $ev->title }}</div>
                    @endforeach
                </td>
            @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
