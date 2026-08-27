@extends('layouts.app')

@section('title', $category->name . ' — Checklist Master')

@section('content')
<div class="checklist-master-page">
    <x-page-header
        variant="card"
        tone="checklist-master"
        eyebrow="Checklist Master"
        eyebrow-icon="bi-clipboard-check"
        title="{{ $category->name }}"
        lead="Pilih item untuk mengelola pertanyaan checklist."
        back-url="{{ route('checklist-master.index') }}" />

    @if($items->isEmpty())
        <x-empty-state
            icon="bi-clipboard-x"
            title="Belum ada item checklist pada kategori ini."
            text="Tambahkan item type pada kategori ini lebih dulu melalui Master Data." />
    @else
        @php
            $itemIcons = [
                'Fire Extinguisher' => 'bi-fire',
                'CCTV' => 'bi-camera-video',
                'AC' => 'bi-snow',
                'Kursi' => 'bi-person-workspace',
                'Meja' => 'bi-table',
                'Laptop' => 'bi-laptop',
                'Komputer' => 'bi-pc-display',
                'Emergency Exit Door' => 'bi-door-open',
                'Emergency Light' => 'bi-lightbulb',
                'Exit Light Sign' => 'bi-signpost-2',
                'Smoke Detector' => 'bi-cloud-haze2',
                'Fire Alarm' => 'bi-bell',
                'Hydrant' => 'bi-moisture',
                'Heat Detector' => 'bi-thermometer-high',
                'intursion Alarm' => 'bi-bell-slash',
            ];
            $tones = ['primary', 'success', 'info', 'warning', 'secondary', 'danger'];
            $frequencyLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];
        @endphp

        <div class="row g-3">
            @foreach($items as $item)
                @php
                    $icon = $itemIcons[$item->name] ?? 'bi-box-seam';
                    $tone = $tones[$item->id % count($tones)];
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <a href="{{ route('checklist-master.item', $item) }}" class="checklist-master-card tone-{{ $tone }}">
                        <div class="checklist-master-card-icon"><i class="bi {{ $icon }}"></i></div>
                        <div class="checklist-master-card-body">
                            <h6 class="mb-1">{{ $item->name }}</h6>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <code>{{ $item->code }}</code>
                                <span class="badge text-bg-secondary">{{ $frequencyLabels[$item->checklist_frequency] ?? ($item->checklist_frequency ?: '—') }}</span>
                            </div>
                            <span class="checklist-master-card-link">Kelola Pertanyaan <i class="bi bi-arrow-right-short"></i></span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
