@extends('layouts.app')

@section('title', 'Detail — ' . $inventory->asset_code)

@section('content')
@php
    $statusUi = match ($inventory->status) {
        'good' => ['label' => 'Baik', 'class' => 'is-ok', 'icon' => 'bi-check-circle-fill'],
        'need_repair' => ['label' => 'Perlu perbaikan', 'class' => 'is-pending', 'icon' => 'bi-tools'],
        default => ['label' => 'Tidak aktif', 'class' => 'is-offday', 'icon' => 'bi-pause-circle-fill'],
    };
@endphp

<x-page-header
    variant="card"
    tone="inventory-detail"
    eyebrow="Detail compliance inventory"
    eyebrow-icon="bi-box-seam"
    :title="$inventory->asset_code"
    :lead="($inventory->itemType->name ?? 'Item tidak diketahui') . ' · ' . ($inventory->category->name ?? 'Tanpa kategori')"
    :back-url="route('compliance.inventory.index')"
>
    <x-slot:media>
        <span class="record-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
    </x-slot:media>

    <x-slot:actions>
        @can('access-compliance-pdf')
            <a href="{{ route('compliance.report.pdf', $inventory) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> Laporan PDF
            </a>
        @endcan
        @can('manage-inventory')
            <a href="{{ route('compliance.inventory.edit', $inventory) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit inventory
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="record-metrics">
    <div class="record-metric">
        <span class="record-metric__icon"><i class="bi {{ $statusUi['icon'] }}" aria-hidden="true"></i></span>
        <span class="record-metric__body">
            <span class="record-metric__label">Kondisi aset</span>
            <span class="record-metric__value"><span class="status-pill {{ $statusUi['class'] }}">{{ $statusUi['label'] }}</span></span>
        </span>
    </div>

    <div class="record-metric">
        <span class="record-metric__icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
        <span class="record-metric__body">
            <span class="record-metric__label">Area</span>
            <span class="record-metric__value">{{ $inventory->area->name ?? 'Belum ditentukan' }}</span>
        </span>
    </div>

    <div class="record-metric">
        <span class="record-metric__icon"><i class="bi bi-boxes" aria-hidden="true"></i></span>
        <span class="record-metric__body">
            <span class="record-metric__label">Jumlah</span>
            <span class="record-metric__value">{{ number_format($inventory->qty) }} unit</span>
        </span>
    </div>

    <div class="record-metric">
        <span class="record-metric__icon"><i class="bi {{ $inventory->active ? 'bi-toggle-on' : 'bi-toggle-off' }}" aria-hidden="true"></i></span>
        <span class="record-metric__body">
            <span class="record-metric__label">Pencatatan</span>
            <span class="record-metric__value">{{ $inventory->active ? 'Aktif' : 'Dinonaktifkan' }}</span>
        </span>
    </div>
</div>

<div class="detail-layout">
    <div class="detail-stack">
        <section class="detail-card">
            <header class="detail-card__header">
                <h2 class="detail-card__title"><i class="bi bi-card-list" aria-hidden="true"></i> Identitas aset</h2>
            </header>
            <div class="detail-card__body">
                <div class="detail-fields">
                    <div class="detail-field">
                        <span class="detail-field__label">Nomor inventory</span>
                        <span class="detail-field__value text-mono">{{ $inventory->asset_code }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-field__label">Kategori</span>
                        <span class="detail-field__value">{{ $inventory->category->name ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-field__label">Nama item</span>
                        <span class="detail-field__value">{{ $inventory->itemType->name ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-field__label">Tipe / spesifikasi</span>
                        <span class="detail-field__value {{ $inventory->type_description ? '' : 'is-muted' }}">{{ $inventory->type_description ?: 'Belum diisi' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-field__label">Tanggal kedaluwarsa</span>
                        <span class="detail-field__value">
                            @if($inventory->expired_date)
                                {{ date('d/m/Y', strtotime($inventory->expired_date)) }}
                                @if($inventory->isExpired())
                                    <span class="status-pill is-late ms-1"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>Kedaluwarsa</span>
                                @endif
                            @else
                                <span class="is-muted">Tidak ditentukan</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-field__label">Terakhir diperbarui</span>
                        <span class="detail-field__value">{{ $inventory->updated_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <header class="detail-card__header">
                <h2 class="detail-card__title"><i class="bi bi-pin-map" aria-hidden="true"></i> Penempatan &amp; tanggung jawab</h2>
            </header>
            <div class="detail-card__body">
                <div class="detail-fields">
                    <div class="detail-field">
                        <span class="detail-field__label">Area utama</span>
                        <span class="detail-field__value">{{ $inventory->area->name ?? 'Belum ditentukan' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-field__label">Lokasi spesifik</span>
                        <span class="detail-field__value {{ $inventory->specific_area ? '' : 'is-muted' }}">{{ $inventory->specific_area ?: 'Belum diisi' }}</span>
                    </div>
                    <div class="detail-field detail-field--wide">
                        <span class="detail-field__label">Person in charge (maks. 2)</span>
                        <span class="detail-field__value">
                            @forelse($inventory->pics as $pic)
                                <span class="badge rounded-pill text-bg-light border me-1 mb-1">
                                    <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $pic->name }}
                                </span>
                            @empty
                                <span class="is-muted">Belum ada PIC yang ditugaskan</span>
                            @endforelse
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-card">
            <header class="detail-card__header">
                <h2 class="detail-card__title"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Catatan</h2>
            </header>
            <div class="detail-card__body">
                <p class="mb-0 {{ $inventory->remark ? 'text-body' : 'text-body-secondary' }}" style="white-space: pre-line">{{ $inventory->remark ?: 'Belum ada catatan untuk inventory ini.' }}</p>
            </div>
        </section>
    </div>

    <aside class="detail-aside">
        <section class="detail-card">
            <header class="detail-card__header">
                <h2 class="detail-card__title"><i class="bi bi-qr-code" aria-hidden="true"></i> QR inventory</h2>
                @if($inventory->qr_image)<span class="status-pill is-ok">Siap dipindai</span>@endif
            </header>
            @if($inventory->qr_image)
                <div class="detail-media detail-media--qr">
                    <img src="{{ route('files.show', ['category' => 'qr', 'path' => $inventory->qr_image]) }}" alt="QR {{ $inventory->asset_code }}" width="220" height="220">
                </div>
                <p class="detail-media__caption">QR mempertahankan URL kompatibel dengan sistem legacy.</p>
            @else
                <div class="detail-media__empty">
                    <i class="bi bi-qr-code" aria-hidden="true"></i>
                    <span>QR belum tersedia.</span>
                </div>
            @endif
        </section>

        <section class="detail-card">
            <header class="detail-card__header">
                <h2 class="detail-card__title"><i class="bi bi-image" aria-hidden="true"></i> Foto inventory</h2>
            </header>
            @if($inventory->photo)
                <div class="detail-media">
                    <img src="{{ route('files.show', ['category' => 'inventory', 'path' => $inventory->photo]) }}" alt="Foto {{ $inventory->asset_code }}" loading="lazy">
                </div>
            @else
                <div class="detail-media__empty">
                    <i class="bi bi-image" aria-hidden="true"></i>
                    <span>Belum ada foto inventory.</span>
                </div>
            @endif
        </section>
    </aside>
</div>
@endsection
