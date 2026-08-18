<?php

namespace Tests\Feature\MasterData;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\Holiday;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    public function test_admin_can_manage_areas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('master-data.areas.store'), ['name' => 'Gedung A'])
            ->assertRedirect();

        $this->assertDatabaseHas('areas', ['name' => 'Gedung A']);

        $area = Area::where('name', 'Gedung A')->firstOrFail();

        $this->actingAs($admin)->put(route('master-data.areas.update', $area), ['name' => 'Gedung A1', 'active' => true])
            ->assertRedirect();
        $this->assertDatabaseHas('areas', ['id' => $area->id, 'name' => 'Gedung A1']);

        $this->actingAs($admin)->delete(route('master-data.areas.destroy', $area))->assertRedirect();
        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
    }

    public function test_asset_item_type_uses_code_as_business_identifier(): void
    {
        $admin = $this->admin();
        $category = InventoryCategory::create(['name' => 'Fire Safety', 'code' => 'FS']);

        $this->actingAs($admin)->post(route('master-data.item-types.store'), [
            'inventory_category_id' => $category->id,
            'name' => 'APAR',
            'code' => 'APAR',
            'checklist_frequency' => 'monthly',
        ])->assertRedirect();

        // Q-015: resolve by code, not by id.
        $this->assertNotNull(AssetItemType::findByCode('APAR'));
    }

    public function test_item_type_code_must_be_unique(): void
    {
        $admin = $this->admin();
        $category = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'monthly']);

        $this->actingAs($admin)->post(route('master-data.item-types.store'), [
            'inventory_category_id' => $category->id,
            'name' => 'APAR Lain',
            'code' => 'APAR',
            'checklist_frequency' => 'daily',
        ])->assertSessionHasErrors('code');
    }

    public function test_holiday_date_must_be_unique(): void
    {
        $admin = $this->admin();
        Holiday::create(['holiday_date' => '2026-01-01', 'description' => 'Tahun Baru']);

        $this->actingAs($admin)->post(route('master-data.holidays.store'), [
            'holiday_date' => '2026-01-01',
            'description' => 'Duplikat',
        ])->assertSessionHasErrors('holiday_date');
    }

    public function test_authenticated_user_can_view_master_data_index(): void
    {
        $user = User::factory()->create(['permission' => 'read']);

        $this->actingAs($user)->get(route('master-data.areas.index'))->assertOk();
    }
}
