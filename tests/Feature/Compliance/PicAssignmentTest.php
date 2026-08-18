<?php

namespace Tests\Feature\Compliance;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PicAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    protected function makeContext(): array
    {
        $category = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);
        $area = Area::create(['name' => 'A']);

        return [$category, $itemType, $area];
    }

    public function test_pics_table_has_no_primary_column(): void
    {
        // Q-007: no primary/secondary hierarchy.
        $this->assertFalse(Schema::hasColumn('compliance_inventory_pics', 'is_primary'));
    }

    public function test_can_assign_two_equal_pics(): void
    {
        Storage::fake('qr');
        [$category, $itemType, $area] = $this->makeContext();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'status' => 'good', 'qty' => 1,
            'pic_ids' => [$u1->id, $u2->id],
        ])->assertRedirect();

        $inventory = ComplianceInventory::firstOrFail();
        // Source of truth = compliance_inventory_pics; both equal.
        $this->assertEqualsCanonicalizing([$u1->id, $u2->id], $inventory->pics->pluck('id')->all());
    }

    public function test_cannot_assign_more_than_two_pics(): void
    {
        Storage::fake('qr');
        [$category, $itemType, $area] = $this->makeContext();
        $users = User::factory()->count(3)->create();

        // Q-007: max 2 PIC.
        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'status' => 'good', 'qty' => 1,
            'pic_ids' => $users->pluck('id')->all(),
        ])->assertSessionHasErrors('pic_ids');
    }

    public function test_pic_can_be_reassigned_on_update(): void
    {
        [$category, $itemType, $area] = $this->makeContext();
        $inventory = ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $this->actingAs($this->admin())->put(route('compliance.inventory.update', $inventory), [
            'status' => 'good', 'qty' => 1, 'pic_ids' => [$u2->id],
        ])->assertRedirect();

        $this->assertEquals([$u2->id], $inventory->fresh()->pics->pluck('id')->all());
    }
}
