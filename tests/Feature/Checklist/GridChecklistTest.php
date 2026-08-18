<?php

namespace Tests\Feature\Checklist;

use App\Actions\Checklist\SaveGridChecklist;
use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GridChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function checker(): User
    {
        return User::factory()->create(['role' => 'compliance', 'permission' => 'write', 'name' => 'Budi']);
    }

    protected function makeItemType(bool $allowNa = false): array
    {
        $category = InventoryCategory::create(['name' => 'First Aid', 'code' => 'FA']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'P3K', 'code' => 'P3K', 'checklist_frequency' => 'daily', 'allow_na' => $allowNa]);
        $area = Area::create(['name' => 'A']);
        $question = ChecklistMaster::create(['asset_item_type_id' => $itemType->id, 'question' => 'Isi lengkap?', 'frequency' => 'daily', 'active' => true]);

        return [$itemType, $area, $question];
    }

    protected function inventory(AssetItemType $itemType, Area $area, string $code): ComplianceInventory
    {
        return ComplianceInventory::create([
            'inventory_category_id' => $itemType->inventory_category_id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'asset_code' => $code, 'status' => 'good', 'qty' => 1,
        ]);
    }

    public function test_grid_not_ok_bypasses_evidence_validation(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');

        // Q-016: grid may bypass NOT_OK evidence (no remark/photo) — e.g. 20+ P3K daily.
        $written = SaveGridChecklist::set($inventory, [
            ['checklist_master_id' => $question->id, 'status' => 'not_ok', 'remark' => null, 'photo' => null, 'time_slot' => null],
        ], $this->checker());

        $this->assertSame(1, $written);
        $this->assertDatabaseHas('checklist_logs', ['checklist_master_id' => $question->id, 'status' => 'not_ok', 'mode' => 'grid']);
    }

    public function test_mark_all_fills_only_empty_cells(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $invA = $this->inventory($itemType, $area, 'FA-P3K-001');
        $invB = $this->inventory($itemType, $area, 'FA-P3K-002');
        $checker = $this->checker();

        // pre-answer one cell for invA
        SaveGridChecklist::set($invA, [
            ['checklist_master_id' => $question->id, 'status' => 'not_ok', 'remark' => null, 'photo' => null, 'time_slot' => null],
        ], $checker);

        // BR-15: mark-all fills only EMPTY cells, never overwrites
        $written = SaveGridChecklist::markAll($itemType, 'ok', $checker);

        $this->assertSame(1, $written); // only invB's empty cell
        $this->assertDatabaseHas('checklist_logs', ['inventory_id' => $invA->id, 'status' => 'not_ok']); // untouched
        $this->assertDatabaseHas('checklist_logs', ['inventory_id' => $invB->id, 'status' => 'ok']);
    }

    public function test_clear_removes_current_period_grid_cells(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $this->inventory($itemType, $area, 'FA-P3K-001');
        $checker = $this->checker();

        SaveGridChecklist::markAll($itemType, 'ok', $checker);
        $this->assertSame(1, ChecklistLog::count());

        // BR-16: clear removes the period's cells
        $deleted = SaveGridChecklist::clear($itemType);

        $this->assertSame(1, $deleted);
        $this->assertSame(0, ChecklistLog::count());
    }

    public function test_grid_na_follows_allow_na(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType(allowNa: false);
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');

        // NA still follows allow_na even in grid (Q-016)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        SaveGridChecklist::set($inventory, [
            ['checklist_master_id' => $question->id, 'status' => 'na', 'remark' => null, 'photo' => null, 'time_slot' => null],
        ], $this->checker());
    }

    public function test_grid_mass_entry_via_http(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $invA = $this->inventory($itemType, $area, 'FA-P3K-001');
        $invB = $this->inventory($itemType, $area, 'FA-P3K-002');

        $this->actingAs($this->checker())->post(route('compliance.checklist.grid.set', $itemType), [
            "cell_{$invA->id}_{$question->id}" => 'ok',
            "cell_{$invB->id}_{$question->id}" => 'not_ok',  // grid bypasses evidence
        ])->assertRedirect();

        $this->assertSame(2, ChecklistLog::where('mode', 'grid')->count());
    }
}
