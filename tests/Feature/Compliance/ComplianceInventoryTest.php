<?php

namespace Tests\Feature\Compliance;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplianceInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    protected function makeContext(): array
    {
        $category = InventoryCategory::create(['name' => 'Fire Safety', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);
        $area = Area::create(['name' => 'Gedung A']);

        return [$category, $itemType, $area];
    }

    public function test_admin_can_create_inventory_with_generated_code_and_qr(): void
    {
        Storage::fake('qr');
        [$category, $itemType, $area] = $this->makeContext();

        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id,
            'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id,
            'specific_area' => 'Lt. 1',
            'status' => 'good',
            'qty' => 1,
        ])->assertRedirect(route('compliance.inventory.index'));

        $inventory = ComplianceInventory::firstOrFail();
        $this->assertSame('FS-APAR-001', $inventory->asset_code);   // BR-19 legacy format
        $this->assertSame('Lt. 1', $inventory->specific_area);       // Q-019
        $this->assertNotNull($inventory->qr_image);                  // Q-021
        Storage::disk('qr')->assertExists($inventory->qr_image);
    }

    public function test_status_must_be_canonical(): void
    {
        Storage::fake('qr');
        [$category, $itemType, $area] = $this->makeContext();

        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id,
            'asset_item_type_id' => $itemType->id,
            'status' => 'banana',
            'qty' => 1,
        ])->assertSessionHasErrors('status');   // Q-017
    }

    public function test_edit_locks_category_area_and_item_type(): void
    {
        [$category, $itemType, $area] = $this->makeContext();
        $other = Area::create(['name' => 'Gedung B']);
        $inventory = ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);

        // BR-45: posting different category/area/item_type must NOT change them.
        $this->actingAs($this->admin())->put(route('compliance.inventory.update', $inventory), [
            'inventory_category_id' => 999,
            'area_id' => $other->id,
            'asset_item_type_id' => 999,
            'status' => 'need_repair',
            'qty' => 2,
            'specific_area' => 'Line B',
        ])->assertRedirect();

        $inventory->refresh();
        $this->assertSame($category->id, $inventory->inventory_category_id);
        $this->assertSame($itemType->id, $inventory->asset_item_type_id);
        $this->assertSame($area->id, $inventory->area_id);          // locked
        $this->assertSame('need_repair', $inventory->status);        // editable
        $this->assertSame('Line B', $inventory->specific_area);
    }

    public function test_expired_does_not_auto_mean_not_active(): void
    {
        [$category, $itemType, $area] = $this->makeContext();
        $inventory = ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'asset_code' => 'FS-APAR-009', 'status' => 'good', 'qty' => 1,
            'expired_date' => '2020-01-01',
        ]);

        // Q-018: GOOD + EXPIRED is valid; expired is independent of status.
        $this->assertTrue($inventory->isExpired());
        $this->assertSame('good', $inventory->status);
    }

    public function test_read_only_user_cannot_create_inventory(): void
    {
        $reader = User::factory()->create(['role' => 'compliance', 'permission' => 'read']);
        [$category, $itemType, $area] = $this->makeContext();

        $this->actingAs($reader)->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id, 'status' => 'good', 'qty' => 1,
        ])->assertForbidden();   // global write-guard
    }
}
