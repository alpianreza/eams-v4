<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\AssetItemType;
use App\Models\ChecklistMaster;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChecklistMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-master-data')->only([
            'storeQuestion', 'updateQuestion', 'destroyQuestion', 'updateFrequency',
        ]);
    }

    /** LEVEL 1 — grid kategori inventory aktif. */
    public function index(): View
    {
        $categories = InventoryCategory::where('active', true)->orderBy('name')->get();

        return view('checklist-master.index', ['categories' => $categories]);
    }

    /** LEVEL 2 — item type aktif milik satu kategori. */
    public function category(InventoryCategory $category): View
    {
        $items = AssetItemType::where('inventory_category_id', $category->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('checklist-master.items', [
            'category' => $category,
            'items' => $items,
        ]);
    }

    /** LEVEL 3 — daftar pertanyaan checklist untuk satu item type. */
    public function item(AssetItemType $itemType): View
    {
        $questions = ChecklistMaster::where('asset_item_type_id', $itemType->id)
            ->orderBy('id')
            ->get();

        return view('checklist-master.detail', [
            'itemType' => $itemType,
            'questions' => $questions,
            'frequency' => $itemType->checklist_frequency,
        ]);
    }

    public function storeQuestion(Request $request, AssetItemType $itemType): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
        ]);

        ChecklistMaster::create([
            'asset_item_type_id' => $itemType->id,
            'question' => $data['question'],
            'frequency' => null,
            'require_photo' => $request->boolean('require_photo'),
            'active' => true,
        ]);

        return back()->with('status', 'Pertanyaan checklist ditambahkan.');
    }

    public function updateQuestion(Request $request, ChecklistMaster $master): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
        ]);

        $master->update([
            'question' => $data['question'],
            'require_photo' => $request->boolean('require_photo'),
            'active' => $request->boolean('active'),
        ]);

        return back()->with('status', 'Pertanyaan checklist diperbarui.');
    }

    public function destroyQuestion(ChecklistMaster $master): RedirectResponse
    {
        $master->delete();

        return back()->with('status', 'Pertanyaan checklist dihapus.');
    }

    public function updateFrequency(Request $request, AssetItemType $itemType): RedirectResponse
    {
        $data = $request->validate([
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        $itemType->update(['checklist_frequency' => $data['frequency']]);

        return back()->with('status', 'Frekuensi checklist diperbarui.');
    }
}
