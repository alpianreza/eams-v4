<?php

namespace Tests\Feature\Compliance;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrCompatTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInventory(): ComplianceInventory
    {
        $category = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);
        $area = Area::create(['name' => 'A']);

        return ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);
    }

    public function test_legacy_qr_detail_route_resolves(): void
    {
        $inventory = $this->makeInventory();
        $user = User::factory()->create();

        // Q-021: the legacy QR URL keeps working after migration.
        $this->actingAs($user)->get('compliance/inventory/detail/'.$inventory->id)->assertOk();
    }

    public function test_qr_payload_is_identical_to_legacy(): void
    {
        $inventory = $this->makeInventory();

        $this->assertSame(
            url('compliance/inventory/detail/'.$inventory->id),
            app(QrService::class)->detailUrl($inventory)
        );
    }

    public function test_qr_image_is_generated_on_configurable_disk(): void
    {
        Storage::fake('qr');
        $inventory = $this->makeInventory();

        $path = app(QrService::class)->generate($inventory);

        Storage::disk('qr')->assertExists($path);
    }
}
