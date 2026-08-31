<?php

namespace App\Livewire\Checklist;

use App\Actions\Checklist\SaveGridChecklist;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Support\Checklist\ChecklistPeriod;
use App\Support\Checklist\ChecklistSlot;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Interactive presentation boundary for the official GRID checklist channel.
 *
 * Domain validation, periods, deduplication, history, and persistence remain
 * in SaveGridChecklist, SubmitChecklist, and ChecklistPeriod. This component
 * only resolves the current active matrix and delegates user intent.
 */
class GridChecklist extends Component
{
    #[Locked]
    public int $itemTypeId;

    public ?int $activeInventoryId = null;

    public ?int $activeQuestionId = null;

    public ?string $activeSlot = null;

    public string $selectedStatus = ChecklistLog::STATUS_OK;

    public ?string $pendingClearSlot = null;

    public function mount(AssetItemType $itemType): void
    {
        $this->itemTypeId = $itemType->id;
    }

    public function openCell(int $inventoryId, int $questionId, ?string $slot = null): void
    {
        $this->assertWriteAccess();
        $this->resolveCell($inventoryId, $questionId, $slot);

        $this->activeInventoryId = $inventoryId;
        $this->activeQuestionId = $questionId;
        $this->activeSlot = $slot;
    }

    public function closeCell(): void
    {
        $this->activeInventoryId = null;
        $this->activeQuestionId = null;
        $this->activeSlot = null;
        $this->resetValidation();
    }

    public function saveActiveCell(): void
    {
        if ($this->activeInventoryId === null || $this->activeQuestionId === null) {
            throw ValidationException::withMessages(['cell' => 'Pilih sel grid terlebih dahulu.']);
        }

        $this->setCell($this->activeInventoryId, $this->activeQuestionId, $this->selectedStatus, $this->activeSlot);
        $this->closeCell();
    }

    public function setCell(int $inventoryId, int $questionId, string $status, ?string $slot = null): void
    {
        $this->assertWriteAccess();
        [$itemType, $inventory, $question, $normalizedSlot] = $this->resolveCell($inventoryId, $questionId, $slot);

        SaveGridChecklist::set($inventory, [[
            'checklist_master_id' => $question->id,
            'status' => $status,
            'remark' => null,
            'photo' => null,
            'time_slot' => $normalizedSlot,
        ]], auth()->user());

        $this->dispatch('eams:toast', type: 'success', message: 'Sel grid tersimpan.');
    }

    public function markAll(string $status = ChecklistLog::STATUS_OK, ?string $slot = null): void
    {
        $this->assertWriteAccess();
        $itemType = $this->itemType();
        $slot = ChecklistSlot::normalize($itemType, $slot);

        $written = SaveGridChecklist::markAll($itemType, $status, auth()->user(), timeSlot: $slot);
        $this->dispatch('eams:toast', type: 'success', message: "Mark-all mengisi {$written} sel kosong.");
    }

    public function clear(?string $slot = null): void
    {
        $this->assertWriteAccess();
        $itemType = $this->itemType();

        if ($slot !== null) {
            $slot = ChecklistSlot::normalize($itemType, $slot);
        }

        $deleted = SaveGridChecklist::clear($itemType, timeSlot: $slot);
        $this->closeCell();
        $this->pendingClearSlot = null;
        $this->dispatch('eams:toast', type: 'success', message: "Clear menghapus {$deleted} sel.");
    }

    public function requestClear(?string $slot = null): void
    {
        $this->assertWriteAccess();
        $itemType = $this->itemType();

        if ($slot !== null) {
            $slot = ChecklistSlot::normalize($itemType, $slot);
        }

        $this->pendingClearSlot = $slot;
        $this->dispatch('eams-confirm', name: 'grid-clear', message: 'Hapus semua sel grid untuk periode ini?');
    }

    public function render(): View
    {
        $itemType = $this->itemType();
        $now = Carbon::now();
        $periodKey = ChecklistPeriod::periodKey($itemType->checklist_frequency, $now);
        $toilet = ChecklistSlot::isRequired($itemType);
        $inventories = $itemType->inventories()
            ->with('area')
            ->where('active', true)
            ->orderBy('asset_code')
            ->get();
        $questions = $itemType->checklistQuestions()
            ->where('active', true)
            ->orderBy('id')
            ->get();
        $existing = ChecklistLog::query()
            ->where('asset_item_type_id', $itemType->id)
            ->where('period_key', $periodKey)
            ->get()
            ->groupBy('inventory_id')
            ->map(fn ($logs) => $logs->groupBy('time_slot')->map(
                fn ($slotLogs) => $slotLogs->keyBy('checklist_master_id')
            ));

        return view('livewire.checklist.grid-checklist', [
            'itemType' => $itemType,
            'inventories' => $inventories,
            'questions' => $questions,
            'existing' => $existing,
            'periodKey' => $periodKey,
            'editable' => ChecklistPeriod::isEditable($itemType->checklist_frequency, $now, $now),
            'allowNa' => (bool) $itemType->allow_na,
            'toilet' => $toilet,
            'toiletSlots' => ChecklistSlot::TOILET_SLOTS,
            'canWrite' => auth()->user()?->hasWriteAccess() ?? false,
        ]);
    }

    /** @return array{0: AssetItemType, 1: ComplianceInventory, 2: ChecklistMaster, 3: ?string} */
    protected function resolveCell(int $inventoryId, int $questionId, ?string $slot): array
    {
        $itemType = $this->itemType();
        $inventory = ComplianceInventory::query()
            ->whereKey($inventoryId)
            ->where('asset_item_type_id', $itemType->id)
            ->where('active', true)
            ->firstOrFail();
        $question = ChecklistMaster::query()
            ->whereKey($questionId)
            ->where('asset_item_type_id', $itemType->id)
            ->where('active', true)
            ->firstOrFail();

        return [$itemType, $inventory, $question, ChecklistSlot::normalize($itemType, $slot)];
    }

    protected function itemType(): AssetItemType
    {
        return AssetItemType::query()->findOrFail($this->itemTypeId);
    }

    protected function assertWriteAccess(): void
    {
        if (! auth()->user()?->hasWriteAccess()) {
            throw new AuthorizationException('Akses tulis diperlukan untuk mengubah checklist.');
        }
    }
}
