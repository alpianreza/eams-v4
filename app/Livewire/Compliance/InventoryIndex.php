<?php

namespace App\Livewire\Compliance;

use App\Models\Area;
use App\Models\ComplianceInventory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Query-state + presentation boundary for the Compliance Inventory list.
 *
 * Business rules remain in ComplianceInventoryController (validation, BR-19
 * asset_code, Q-021 QR, Q-022 storage) plus GenerateAssetCode, QrService, and
 * FileStorage. This component only owns list query state (filters, pagination)
 * and renders it with the Tailwind design system. The query mirrors the
 * original controller's index() one-to-one.
 */
class InventoryIndex extends Component
{
    use WithPagination;

    /** Legacy-compatible search over asset_code (same LIKE semantics as before). */
    #[Url(history: true)]
    public string $q = '';

    #[Url(history: true)]
    public string $areaId = '';

    /** Canonical inventory status filter (Q-017): good | need_repair | not_active. */
    #[Url(history: true)]
    public string $status = '';

    /** Identical page size to the previous controller behavior. */
    protected int $perPage = 20;

    /** EAMS design-system pagination (no Bootstrap markup, no SVG). */
    public function paginationView(): string
    {
        return 'livewire.compliance.inventory-pagination';
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedAreaId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->q = '';
        $this->areaId = '';
        $this->status = '';
        $this->resetPage();
    }

    /** Mirror of the original ComplianceInventoryController::index() query. */
    protected function inventories()
    {
        return ComplianceInventory::with(['category', 'itemType', 'area', 'pics'])
            ->when($this->areaId !== '', fn ($query) => $query->where('area_id', (int) $this->areaId))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->q !== '', fn ($query) => $query->where('asset_code', 'like', '%'.$this->q.'%'))
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Full-page render into the classic @extends layout: the app shell uses
     * @yield('content'), so we register the section/extends pair here.
     */
    public function render(): View
    {
        return view('livewire.compliance.inventory-index', [
            'inventories' => $this->inventories(),
            'areas' => Area::where('active', true)->orderBy('name')->get(),
            'canManage' => auth()->user()->can('manage-inventory'),
        ])
            ->title('Compliance Inventory')
            ->section('content')
            ->extends('layouts.app');
    }
}
