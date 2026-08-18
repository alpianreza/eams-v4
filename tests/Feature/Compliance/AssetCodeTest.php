<?php

namespace Tests\Feature\Compliance;

use App\Actions\Compliance\GenerateAssetCode;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    protected function makeTypes(): array
    {
        $category = InventoryCategory::create(['name' => 'Fire Safety', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);

        return [$category, $itemType];
    }

    public function test_generator_uses_legacy_format_and_increments(): void
    {
        [$category, $itemType] = $this->makeTypes();

        // BR-19: CATEGORYCODE-ITEMCODE-###.
        $this->assertSame('FS-APAR-001', GenerateAssetCode::generate($category, $itemType));

        ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);

        $this->assertSame('FS-APAR-002', GenerateAssetCode::generate($category, $itemType));
    }

    public function test_provided_asset_code_is_preserved_exactly(): void
    {
        Storage::fake('qr');
        [$category, $itemType] = $this->makeTypes();

        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'asset_code' => 'APAR-CUSTOM-07', 'status' => 'good', 'qty' => 1,
        ])->assertRedirect();

        // Q-020: preserved exactly, not regenerated.
        $this->assertSame('APAR-CUSTOM-07', ComplianceInventory::firstOrFail()->asset_code);
    }

    public function test_duplicate_asset_code_is_rejected_not_renamed(): void
    {
        Storage::fake('qr');
        [$category, $itemType] = $this->makeTypes();
        ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);

        // Q-020: duplicate → FAIL/REPORT, never auto-rename.
        $this->actingAs($this->admin())->post(route('compliance.inventory.store'), [
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ])->assertSessionHasErrors('asset_code');

        $this->assertSame(1, ComplianceInventory::where('asset_code', 'FS-APAR-001')->count());
    }
}
