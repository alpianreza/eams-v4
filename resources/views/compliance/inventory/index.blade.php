@extends('layouts.app')

@section('title', 'Compliance Inventory')

@section('content')
@php
    $canManage = auth()->user()->can('manage-inventory');
    $hasFilters = request()->filled('q') || request()->filled('area_id') || request()->filled('status');
@endphp

<x-page-header
    variant="card"
    tone="inventory"
    eyebrow="Compliance"
    eyebrow-icon="bi-box-seam"
    title="Compliance Inventory"
    lead="Pantau identitas, lokasi, PIC, kondisi, masa berlaku, dan QR seluruh aset compliance."
>
    <x-slot:actions>
        @if($canManage)
            <a href="{{ route('compliance.inventory.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Tambah inventory
            </a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="filter-panel">
    <form method="GET" class="inventory-filter" aria-label="Filter compliance inventory">
        <div class="filter-field">
            <label for="inventory-search" class="filter-field__label">Cari inventory</label>
            <div class="filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="inventory-search" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Kode inventory...">
            </div>
        </div>

        <div class="filter-field">
            <label for="inventory-area" class="filter-field__label">Area</label>
            <select id="inventory-area" name="area_id" class="form-select">
                <option value="">Semua area</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" @selected((string) request('area_id') === (string) $area->id)>{{ $area->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="inventory-status" class="filter-field__label">Status aset</label>
            <select id="inventory-status" name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="good" @selected(request('status') === 'good')>Baik</option>
                <option value="need_repair" @selected(request('status') === 'need_repair')>Perlu perbaikan</option>
                <option value="not_active" @selected(request('status') === 'not_active')>Tidak aktif</option>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-funnel me-1" aria-hidden="true"></i> Terapkan
            </button>
            @if($hasFilters)
                <a href="{{ route('compliance.inventory.index') }}" class="btn btn-outline-secondary" aria-label="Reset filter" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<div class="list-toolbar">
    <div>
        <strong>{{ number_format($inventories->total()) }}</strong> inventory ditemukan
        @if($hasFilters)<span>berdasarkan filter aktif</span>@endif
    </div>
    <span>Urut dari data terbaru</span>
</div>

<div class="table-shell">
    <div class="table-responsive">
        <table class="table table-hover inventory-table align-middle">
            <thead>
                <tr>
                    <th>Inventory</th>
                    <th>Item &amp; kategori</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>PIC</th>
                    <th>Masa berlaku</th>
                    <th class="text-center">QR</th>
                    @if($canManage)<th class="text-end">Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($inventories as $inv)
                @php
                    $statusUi = match ($inv->status) {
                        'good' => ['label' => 'Baik', 'class' => 'is-ok', 'icon' => 'bi-check-circle-fill'],
                        'need_repair' => ['label' => 'Perlu perbaikan', 'class' => 'is-pending', 'icon' => 'bi-tools'],
                        default => ['label' => 'Tidak aktif', 'class' => 'is-offday', 'icon' => 'bi-pause-circle-fill'],
                    };
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('compliance.inventory.detail', $inv) }}" class="asset-link">
                            <span class="asset-link__icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                            <span class="asset-link__text">
                                <span class="asset-link__code">{{ $inv->asset_code }}</span>
                                <span class="asset-link__meta">{{ $inv->type_description ?: 'Tanpa spesifikasi' }}</span>
                            </span>
                        </a>
                    </td>
                    <td>
                        <span class="cell-primary">{{ $inv->itemType->name ?? '—' }}</span>
                        <span class="cell-secondary">{{ $inv->category->name ?? 'Tanpa kategori' }}</span>
                    </td>
                    <td>
                        <span class="cell-primary">{{ $inv->area->name ?? '—' }}</span>
                        @if($inv->specific_area)<span class="cell-secondary">{{ $inv->specific_area }}</span>@endif
                    </td>
                    <td>
                        <span class="status-pill {{ $statusUi['class'] }}">
                            <i class="bi {{ $statusUi['icon'] }}" aria-hidden="true"></i>{{ $statusUi['label'] }}
                        </span>
                    </td>
                    <td>
                        @if($inv->pics->isNotEmpty())
                            <div class="pic-list" title="{{ $inv->pics->pluck('name')->join(', ') }}">
                                <span class="d-flex">
                                    @foreach($inv->pics->take(2) as $pic)
                                        <span class="pic-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($pic->name, 0, 2)) }}</span>
                                    @endforeach
                                </span>
                                <span class="pic-list__names">{{ $inv->pics->pluck('name')->join(', ') }}</span>
                            </div>
                        @else
                            <span class="text-body-secondary">—</span>
                        @endif
                    </td>
                    <td>
                        @if($inv->expired_date)
                            <span class="cell-primary {{ $inv->isExpired() ? 'text-danger' : '' }}">{{ date('d/m/Y', strtotime($inv->expired_date)) }}</span>
                            <span class="cell-secondary">{{ $inv->isExpired() ? 'Sudah kedaluwarsa' : 'Tercatat' }}</span>
                        @else
                            <span class="text-body-secondary">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="qr-indicator {{ $inv->qr_image ? 'is-ready' : '' }}" title="{{ $inv->qr_image ? 'QR tersedia' : 'QR belum tersedia' }}">
                            <i class="bi {{ $inv->qr_image ? 'bi-qr-code' : 'bi-dash' }}" aria-hidden="true"></i>
                        </span>
                    </td>
                    @if($canManage)
                        <td class="text-end">
                            <div class="row-actions">
                                <a href="{{ route('compliance.inventory.detail', $inv) }}" class="btn btn-sm btn-light row-action" title="Lihat detail" aria-label="Lihat detail {{ $inv->asset_code }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('compliance.inventory.edit', $inv) }}" class="btn btn-sm btn-outline-primary row-action" title="Edit" aria-label="Edit {{ $inv->asset_code }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="{{ route('compliance.inventory.destroy', $inv) }}" onsubmit="return confirm('Hapus inventory {{ $inv->asset_code }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger row-action" type="submit" title="Hapus" aria-label="Hapus {{ $inv->asset_code }}">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManage ? 8 : 7 }}">
                        <x-empty-state
                            icon="bi-box-seam"
                            title="Inventory tidak ditemukan"
                            :text="$hasFilters ? 'Coba ubah atau reset filter pencarian.' : 'Belum ada compliance inventory yang tersimpan.'"
                            :boxed="false"
                        >
                            @if($hasFilters)
                                <x-slot:actions>
                                    <a href="{{ route('compliance.inventory.index') }}" class="btn btn-outline-secondary btn-sm">Reset filter</a>
                                </x-slot:actions>
                            @endif
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($inventories->hasPages())
    <div class="pagination-wrap">
        <div class="pagination-meta">
            Menampilkan {{ $inventories->firstItem() }}–{{ $inventories->lastItem() }} dari {{ number_format($inventories->total()) }} data
        </div>
        {{ $inventories->onEachSide(1)->links() }}
    </div>
@endif
@endsection
