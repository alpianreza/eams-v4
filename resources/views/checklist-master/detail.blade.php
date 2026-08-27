@extends('layouts.app')

@section('title', $itemType->name . ' — Checklist Master')

@section('content')
@php
    $frequencyLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];
    $frequencyLabel = $frequencyLabels[$frequency] ?? ($frequency ?: '—');
    $totalQuestions = $questions->count();
    $activeQuestions = $questions->where('active', true)->count();
    $photoRequiredQuestions = $questions->where('require_photo', true)->count();
@endphp

<div class="checklist-master-page">
    <x-page-header
        variant="card"
        tone="checklist-master"
        eyebrow="Checklist Master"
        eyebrow-icon="bi-clipboard-check"
        title="{{ $itemType->name }}"
        lead="Kelola pertanyaan checklist untuk item ini."
        back-url="{{ route('checklist-master.category', $itemType->inventory_category_id) }}">
        <x-slot:actions>
            @can('manage-master-data')
            <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAdd">
                <i class="bi bi-plus-circle"></i> Tambah Pertanyaan
            </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <section class="row g-3 mb-3 checklist-master-stats">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Total Pertanyaan" :value="$totalQuestions" icon="bi-list-check" tone="info" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Status Aktif" :value="$activeQuestions" icon="bi-check2-circle" tone="ok" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Wajib Foto" :value="$photoRequiredQuestions" icon="bi-camera" tone="pending" />
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" data-tone="accent">
                <span class="stat-card__icon"><i class="bi bi-calendar-week"></i></span>
                <div class="stat-card__body w-100">
                    <span class="stat-card__label">Frekuensi</span>
                    @can('manage-master-data')
                    <form method="POST" action="{{ route('checklist-master.frequency', $itemType) }}" class="d-flex gap-1 mt-1">
                        @csrf
                        <select name="frequency" class="form-select form-select-sm" aria-label="Frekuensi checklist">
                            <option value="daily" @selected($frequency === 'daily')>Harian</option>
                            <option value="weekly" @selected($frequency === 'weekly')>Mingguan</option>
                            <option value="monthly" @selected($frequency === 'monthly')>Bulanan</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                    </form>
                    @else
                    <span class="stat-card__value">{{ $frequencyLabel }}</span>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <section class="surface-card checklist-master-table-card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0 checklist-master-table">
                <thead>
                    <tr>
                        <th width="60" class="text-center">No</th>
                        <th>Pertanyaan</th>
                        <th width="130" class="text-center">Wajib Foto</th>
                        <th width="120" class="text-center">Status</th>
                        @can('manage-master-data')<th width="140" class="text-center">Aksi</th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $index => $question)
                        <tr>
                            <td class="text-center fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $question->question }}</div>
                                <small class="text-muted">Item: {{ $itemType->name }}</small>
                            </td>
                            <td class="text-center">
                                @if($question->require_photo)
                                    <span class="badge text-bg-info">Ya</span>
                                @else
                                    <span class="badge text-bg-secondary">Tidak</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($question->active)
                                    <span class="status-pill is-ok">Aktif</span>
                                @else
                                    <span class="status-pill is-offday">Nonaktif</span>
                                @endif
                            </td>
                            @can('manage-master-data')
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $question->id }}" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" action="{{ route('checklist-master.question.destroy', $question) }}" class="d-inline" onsubmit="return confirm('Hapus pertanyaan checklist ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('manage-master-data') ? 5 : 4 }}" class="text-center text-muted py-5">Belum ada pertanyaan checklist.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@can('manage-master-data')
@include('checklist-master._modal-add', ['itemType' => $itemType])
@foreach($questions as $question)
    @include('checklist-master._modal-edit', ['master' => $question, 'itemType' => $itemType])
@endforeach
@endcan
@endsection
