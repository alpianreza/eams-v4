<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\AssetItemType;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetItemTypeController extends Controller
{
    public function index(): View
    {
        return view('master-data.item-types.index', [
            'itemTypes' => AssetItemType::with('category')->latest()->paginate(20),
            'categories' => InventoryCategory::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        AssetItemType::create($data + ['active' => true]);

        return back()->with('status', 'Item type ditambahkan.');
    }

    public function update(Request $request, AssetItemType $itemType): RedirectResponse
    {
        $data = $this->validateData($request, $itemType->id);
        $data['active'] = $request->boolean('active');

        $itemType->update($data);

        return back()->with('status', 'Item type diperbarui.');
    }

    public function destroy(AssetItemType $itemType): RedirectResponse
    {
        $itemType->delete();

        return back()->with('status', 'Item type dihapus.');
    }

    /** `code` is the business identifier (Q-015); it must be unique & stable. */
    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('asset_item_types', 'code')->ignore($ignoreId)],
            'checklist_frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'allow_na' => ['sometimes', 'boolean'],
        ]);
    }
}
