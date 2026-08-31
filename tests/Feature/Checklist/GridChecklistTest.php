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

    protected function makeItemType(bool $allowNa = false, string $code = 'P3K', string $frequency = 'daily'): array
    {
        $category = InventoryCategory::create(['name' => 'First Aid '.$code, 'code' => 'FA'.$code]);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => $code, 'code' => $code, 'checklist_frequency' => $frequency, 'allow_na' => $allowNa]);
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

    public function test_grid_route_renders_the_current_item_type_matrix(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType(allowNa: true);
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');

        $this->actingAs($this->checker())
            ->get(route('compliance.checklist.grid', $itemType))
            ->assertOk()
            ->assertSee('Grid Checklist')
            ->assertSee($inventory->asset_code)
            ->assertSee($question->question)
            ->assertSee('Matriks checklist')
            ->assertSee('Pilih sel untuk mengisi status');
    }

    public function test_grid_clear_preserves_standard_mode_logs(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');
        $checker = $this->checker();

        ChecklistLog::create([
            'inventory_id' => $inventory->id,
            'asset_item_type_id' => $itemType->id,
            'checklist_master_id' => $question->id,
            'check_date' => '2026-08-18',
            'period_key' => '2026-08-18',
            'status' => 'ok',
            'checked_by_user_id' => $checker->id,
            'checked_by_name' => $checker->name,
            'mode' => 'standard',
        ]);

        SaveGridChecklist::markAll($itemType, 'ok', $checker);
        $this->actingAs($checker)->post(route('compliance.checklist.grid.clear', $itemType))->assertRedirect();

        $this->assertDatabaseHas('checklist_logs', [
            'inventory_id' => $inventory->id,
            'mode' => 'standard',
        ]);
    }

    public function test_grid_http_ignores_cell_inputs_outside_the_item_type_matrix(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');

        [$otherType, $otherArea, $otherQuestion] = $this->makeItemType(code: 'P3K-OTHER');
        $otherInventory = $this->inventory($otherType, $otherArea, 'FA-P3K-OTHER');

        $this->actingAs($this->checker())->post(route('compliance.checklist.grid.set', $itemType), [
            "cell_{$inventory->id}_{$question->id}" => 'ok',
            "cell_{$otherInventory->id}_{$otherQuestion->id}" => 'not_ok',
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_logs', ['inventory_id' => $inventory->id, 'status' => 'ok']);
        $this->assertDatabaseMissing('checklist_logs', ['inventory_id' => $otherInventory->id]);
    }

    public function test_read_only_user_cannot_mutate_grid_through_legacy_route(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');
        $reader = User::factory()->create(['role' => 'compliance', 'permission' => 'read']);

        $this->actingAs($reader)->post(route('compliance.checklist.grid.set', $itemType), [
            "cell_{$inventory->id}_{$question->id}" => 'ok',
        ])->assertForbidden();

        $this->assertDatabaseCount('checklist_logs', 0);
    }

    public function test_toilet_grid_requires_a_valid_slot(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType(code: 'TOILET');
        $inventory = $this->inventory($itemType, $area, 'FA-TOILET-001');

        $this->actingAs($this->checker())->post(route('compliance.checklist.grid.set', $itemType), [
            "cell_{$inventory->id}_{$question->id}" => 'ok',
        ])->assertSessionHasErrors('time_slot');

        $this->assertDatabaseCount('checklist_logs', 0);
    }

    public function test_toilet_grid_keeps_results_separate_for_each_slot(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType(allowNa: true, code: 'TOILET');
        $inventory = $this->inventory($itemType, $area, 'FA-TOILET-001');
        $checker = $this->checker();

        foreach (['PG' => 'ok', 'SI' => 'not_ok', 'SO' => 'na'] as $slot => $status) {
            $this->actingAs($checker)->post(route('compliance.checklist.grid.set', $itemType), [
                "slot_{$inventory->id}" => $slot,
                "cell_{$inventory->id}_{$question->id}" => $status,
            ])->assertRedirect();
        }

        $this->assertDatabaseHas('checklist_logs', ['time_slot' => 'PG', 'status' => 'ok']);
        $this->assertDatabaseHas('checklist_logs', ['time_slot' => 'SI', 'status' => 'not_ok']);
        $this->assertDatabaseHas('checklist_logs', ['time_slot' => 'SO', 'status' => 'na']);
        $this->assertSame(3, ChecklistLog::count());
    }

    public function test_non_toilet_grid_discards_forged_time_slot(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$itemType, $area, $question] = $this->makeItemType();
        $inventory = $this->inventory($itemType, $area, 'FA-P3K-001');

        $this->actingAs($this->checker())->post(route('compliance.checklist.grid.set', $itemType), [
            "slot_{$inventory->id}" => 'PG',
            "cell_{$inventory->id}_{$question->id}" => 'ok',
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_logs', ['inventory_id' => $inventory->id, 'time_slot' => null]);
    }
}
