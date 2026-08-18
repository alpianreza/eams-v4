<?php

namespace App\Http\Controllers\Compliance;

use App\Actions\Compliance\GenerateAssetCode;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplianceInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $inventories = ComplianceInventory::with(['category', 'itemType', 'area', 'pics'])
            ->when($request->input('area_id'), fn ($q, $v) => $q->where('area_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('q'), fn ($q, $v) => $q->where('asset_code', 'like', '%'.$v.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('compliance.inventory.index', [
            'inventories' => $inventories,
            'areas' => Area::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('compliance.inventory.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $category = InventoryCategory::findOrFail($data['inventory_category_id']);
        $itemType = AssetItemType::findOrFail($data['asset_item_type_id']);

        // asset_code: preserve provided value exactly; otherwise generate in the legacy format (BR-19 / Q-020).
        $provided = trim((string) ($data['asset_code'] ?? ''));
        $data['asset_code'] = $provided !== '' ? $provided : GenerateAssetCode::generate($category, $itemType);

        $inventory = ComplianceInventory::create(collect($data)->except('pic_ids')->toArray() + ['active' => true]);

        // PIC: max 2, equal, no primary — compliance_inventory_pics is the source of truth (Q-007).
        $inventory->pics()->sync($data['pic_ids'] ?? []);

        $inventory->update(['qr_image' => app(QrService::class)->generate($inventory)]);

        return redirect()->route('compliance.inventory.index')
            ->with('status', 'Inventory ditambahkan. Kode: '.$inventory->asset_code);
    }

    /** QR compatibility target (Q-021): identical URL to legacy. */
    public function show(ComplianceInventory $inventory): View
    {
        $inventory->load(['category', 'itemType', 'area', 'pics']);

        return view('compliance.inventory.show', ['inventory' => $inventory]);
    }

    public function edit(ComplianceInventory $inventory): View
    {
        $inventory->load('pics');

        return view('compliance.inventory.edit', $this->formData() + ['inventory' => $inventory]);
    }

    public function update(Request $request, ComplianceInventory $inventory): RedirectResponse
    {
        // BR-45: category / area / item_type are LOCKED on edit — they are not accepted here.
        $data = $request->validate([
            'type_description' => ['nullable', 'string', 'max:255'],
            'specific_area' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(ComplianceInventory::STATUSES)],
            'qty' => ['required', 'integer', 'min:1'],
            'remark' => ['nullable', 'string'],
            'expired_date' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
            'pic_ids' => ['nullable', 'array', 'max:2'],
            'pic_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $inventory->update(collect($data)->except('pic_ids')->toArray());
        $inventory->pics()->sync($data['pic_ids'] ?? []);

        return redirect()->route('compliance.inventory.index')->with('status', 'Inventory diperbarui.');
    }

    public function destroy(ComplianceInventory $inventory): RedirectResponse
    {
        $inventory->delete();

        return redirect()->route('compliance.inventory.index')->with('status', 'Inventory dihapus.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'asset_item_type_id' => ['required', 'exists:asset_item_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            // Q-020: provided code kept exactly; duplicate is rejected (unique), never auto-renamed.
            'asset_code' => ['nullable', 'string', 'max:50', 'unique:compliance_inventories,asset_code'],
            'type_description' => ['nullable', 'string', 'max:255'],
            'specific_area' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(ComplianceInventory::STATUSES)],
            'qty' => ['required', 'integer', 'min:1'],
            'remark' => ['nullable', 'string'],
            'expired_date' => ['nullable', 'date'],
            'pic_ids' => ['nullable', 'array', 'max:2'],
            'pic_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    protected function formData(): array
    {
        return [
            'categories' => InventoryCategory::where('active', true)->orderBy('name')->get(),
            'itemTypes' => AssetItemType::where('active', true)->orderBy('name')->get(),
            'areas' => Area::where('active', true)->orderBy('name')->get(),
            'picUsers' => User::where('status', 'active')->orderBy('name')->get(),
            'statuses' => ComplianceInventory::STATUSES,
        ];
    }
}
