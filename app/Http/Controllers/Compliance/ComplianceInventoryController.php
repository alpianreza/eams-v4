<?php

namespace App\Http\Controllers\Compliance;

use App\Actions\Compliance\GenerateAssetCode;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use App\Services\FileStorage;
use App\Services\QrService;
use App\Support\Checklist\ChecklistPeriod;
use App\Support\Uploads\ImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        $inventory = ComplianceInventory::create(collect($data)->except(['pic_ids', 'photo'])->toArray() + ['active' => true]);
        $inventory->pics()->sync($data['pic_ids'] ?? []);

        // Inventory photo (Q-022/Q-026): stored on the configurable `inventory` disk.
        if ($request->hasFile('photo')) {
            $request->validate(['photo' => ImageUpload::rules()]);
            $inventory->update(['photo' => app(FileStorage::class)->put('inventory', $request->file('photo'))]);
        }

        $inventory->update(['qr_image' => app(QrService::class)->generate($inventory)]);

        return redirect()->route('compliance.inventory.index')
            ->with('status', 'Inventory ditambahkan. Kode: '.$inventory->asset_code);
    }

    /** QR compatibility target (Q-021): identical URL to legacy. */
    public function show(ComplianceInventory $inventory): View
    {
        $inventory->load(['category', 'itemType', 'area', 'pics']);

        // Period history (detail v2): one aggregated row per period, latest first.
        $frequency = $inventory->itemType->checklist_frequency ?? 'monthly';
        $history = ChecklistLog::query()
            ->where('inventory_id', $inventory->id)
            ->select(
                'period_key',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'ok' THEN 1 ELSE 0 END) as ok_count"),
                DB::raw("SUM(CASE WHEN status = 'not_ok' THEN 1 ELSE 0 END) as not_ok_count"),
                DB::raw("SUM(CASE WHEN status = 'na' THEN 1 ELSE 0 END) as na_count"),
                DB::raw('MAX(check_date) as last_check'),
                DB::raw('MAX(checked_by_name) as last_checker')
            )
            ->groupBy('period_key')
            ->orderByDesc('period_key')
            ->limit(24)
            ->get()
            ->map(function ($row) {
                $row->date = $this->periodAnchorDate($row->period_key);
                $row->frequency = $this->periodFrequency($row->period_key);

                return $row;
            });

        // Current-period quick status for the fill CTA.
        $currentKey = ChecklistPeriod::periodKey($frequency, Carbon::now());
        $currentFilled = $history->firstWhere('period_key', $currentKey) !== null;

        return view('compliance.inventory.show', [
            'inventory' => $inventory,
            'history' => $history,
            'currentKey' => $currentKey,
            'currentFilled' => $currentFilled,
        ]);
    }

    /** Anchor date of a period_key (daily = itself, weekly = first day of its slice, monthly = month start). */
    protected function periodAnchorDate(string $periodKey): Carbon
    {
        if (preg_match('/^(\d{4}-\d{2})-W(\d)$/', $periodKey, $m)) {
            $startDay = ((int) $m[2] - 1) * 7 + 1;

            return Carbon::parse($m[1].'-'.str_pad((string) $startDay, 2, '0', STR_PAD_LEFT));
        }

        return Carbon::parse($periodKey);
    }

    protected function periodFrequency(string $periodKey): string
    {
        return match (true) {
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodKey) => 'daily',
            preg_match('/^\d{4}-\d{2}-W\d$/', $periodKey) => 'weekly',
            default => 'monthly',
        };
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

        $inventory->update(collect($data)->except(['pic_ids', 'photo'])->toArray());
        $inventory->pics()->sync($data['pic_ids'] ?? []);

        if ($request->hasFile('photo')) {
            $request->validate(['photo' => ImageUpload::rules()]);
            app(FileStorage::class)->delete('inventory', $inventory->photo);
            $inventory->update(['photo' => app(FileStorage::class)->put('inventory', $request->file('photo'))]);
        }

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
