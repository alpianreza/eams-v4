@extends('layouts.app')

@section('title', 'Checklist Master')

@section('content')
<div class="checklist-master-page">
    <x-page-header
        variant="card"
        tone="checklist-master"
        eyebrow="Checklist Master"
        eyebrow-icon="bi-clipboard-check"
        title="Kategori Checklist Compliance"
        lead="Pilih kategori untuk mengelola item dan pertanyaan checklist." />

    @if($categories->isEmpty())
        <x-empty-state
            icon="bi-layers"
            title="Belum ada kategori inventory aktif."
            text="Aktifkan minimal satu kategori inventory di Master Data untuk mulai menyusun checklist." />
    @else
        @php
            $styleMap = [
                'Fire Safety' => ['tone' => 'danger', 'icon' => 'bi-fire'],
                'HSE' => ['tone' => 'warning', 'icon' => 'bi-cone-striped'],
                'CTPAT' => ['tone' => 'primary', 'icon' => 'bi-shield-check'],
                'EMS' => ['tone' => 'success', 'icon' => 'bi-tree'],
                'Utility' => ['tone' => 'info', 'icon' => 'bi-lightning-charge'],
                'Maintenance' => ['tone' => 'secondary', 'icon' => 'bi-tools'],
                'Maintenance (Machinery)' => ['tone' => 'primary', 'icon' => 'bi-buildings'],
                'Social' => ['tone' => 'success', 'icon' => 'bi-people'],
            ];
        @endphp

        <div class="row g-3">
            @foreach($categories as $category)
                @php
                    $tone = $styleMap[$category->name]['tone'] ?? 'dark';
                    $icon = $styleMap[$category->name]['icon'] ?? 'bi-layers';
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <a href="{{ route('checklist-master.category', $category) }}" class="checklist-master-card tone-{{ $tone }}">
                        <div class="checklist-master-card-icon"><i class="bi {{ $icon }}"></i></div>
                        <div class="checklist-master-card-body">
                            <h6 class="mb-1">{{ $category->name }}</h6>
                            <span class="checklist-master-card-link">Kelola Item <i class="bi bi-arrow-right-short"></i></span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
