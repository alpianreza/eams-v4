<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryCategoryController extends Controller
{
    public function index(): View
    {
        return view('master-data.categories.index', ['categories' => InventoryCategory::latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        InventoryCategory::create($data + ['active' => true]);

        return back()->with('status', 'Kategori ditambahkan.');
    }

    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $category->update($data);

        return back()->with('status', 'Kategori diperbarui.');
    }

    public function destroy(InventoryCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', 'Kategori dihapus.');
    }
}
