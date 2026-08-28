<?php

namespace Tests\Feature\Compliance;

use App\Livewire\Compliance\InventoryIndex;
use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryIndexComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    protected function staff(): User
    {
        // Non-compliance role: manage-inventory gate is false for staff.
        return User::factory()->create(['role' => 'staff', 'permission' => 'read']);
    }

    protected function makeContext(): array
    {
        $category = InventoryCategory::create(['name' => 'Fire Safety', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);
        $area = Area::create(['name' => 'Gedung A']);

        return [$category, $itemType, $area];
    }

    protected function createInventory(array $context, string $code, string $status = 'good', ?int $areaId = null): ComplianceInventory
    {
        [$category, $itemType, $area] = $context;

        return ComplianceInventory::create([
            'inventory_category_id' => $category->id,
            'asset_item_type_id' => $itemType->id,
            'area_id' => $areaId ?? $area->id,
            'asset_code' => $code,
            'status' => $status,
            'qty' => 1,
            'active' => true,
        ]);
    }

    public function test_component_renders_rows_with_canonical_status_presentation(): void
    {
        $context = $this->makeContext();
        $this->createInventory($context, 'FS-APAR-001');
        $this->createInventory($context, 'FS-APAR-002', 'need_repair');

        Livewire::actingAs($this->admin())
            ->test(InventoryIndex::class)
            ->assertStatus(200)
            ->assertSee('FS-APAR-001')
            ->assertSee('FS-APAR-002')
            ->assertSeeHtml('data-status="GOOD"')
            ->assertSeeHtml('data-status="NEED_REPAIR"')
            ->assertSeeHtml('data-eams-component="table"');
    }

    public function test_filters_narrow_results_with_same_query_semantics(): void
    {
        $context = $this->makeContext();
        $this->createInventory($context, 'FS-APAR-001');
        $this->createInventory($context, 'FS-HEAT-002', 'not_active');
        $otherArea = Area::create(['name' => 'Gedung B']);

        Livewire::actingAs($this->admin())
            ->test(InventoryIndex::class)
            ->set('q', 'APAR')
            ->assertSee('FS-APAR-001')
            ->assertDontSee('FS-HEAT-002')
            ->set('q', '')
            ->set('status', 'not_active')
            ->assertSee('FS-HEAT-002')
            ->assertDontSee('FS-APAR-001')
            ->set('status', '')
            ->set('areaId', (string) $otherArea->id)
            ->assertDontSee('FS-APAR-001')
            ->assertDontSee('FS-HEAT-002');
    }

    public function test_pagination_keeps_twenty_per_page_and_supports_navigation(): void
    {
        $context = $this->makeContext();

        foreach (range(1, 21) as $number) {
            $this->createInventory($context, sprintf('FS-APAR-%03d', $number));
        }

        Livewire::actingAs($this->admin())
            ->test(InventoryIndex::class)
            ->assertSee('FS-APAR-020')
            ->assertDontSee('FS-APAR-021')
            ->call('gotoPage', 2)
            ->assertSee('FS-APAR-021')
            ->assertDontSee('FS-APAR-001');
    }

    public function test_manage_actions_follow_existing_gate_semantics(): void
    {
        $context = $this->makeContext();
        $this->createInventory($context, 'FS-APAR-001');

        // staff cannot manage inventory: actions hidden.
        Livewire::actingAs($this->staff())
            ->test(InventoryIndex::class)
            ->assertDontSee('Tambah inventory')
            ->assertDontSee('Hapus FS-APAR-001');

        // compliance role keeps the manage-inventory gate (same as the legacy view).
        $compliance = User::factory()->create(['role' => 'compliance', 'permission' => 'read']);
        Livewire::actingAs($compliance)
            ->test(InventoryIndex::class)
            ->assertSee('Tambah inventory');
    }

    public function test_empty_state_differs_between_filtered_and_unfiltered_views(): void
    {
        $this->makeContext();

        Livewire::actingAs($this->admin())
            ->test(InventoryIndex::class)
            ->assertSee('Belum ada compliance inventory')
            ->set('q', 'ZZZ')
            ->assertSee('Inventory tidak ditemukan');
    }
}
