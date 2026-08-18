<?php

namespace Tests\Feature\Report;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceReportPdfTest extends TestCase
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

    protected function user(string $role, string $permission = 'read'): User
    {
        return User::factory()->create(['role' => $role, 'permission' => $permission]);
    }

    public function test_admin_can_download_compliance_pdf(): void
    {
        $inventory = $this->makeInventory();

        $response = $this->actingAs($this->user('admin', 'write'))
            ->get(route('compliance.report.pdf', $inventory));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_compliance_user_can_download_pdf(): void
    {
        $inventory = $this->makeInventory();

        // Q-008: a user with Compliance access is allowed.
        $this->actingAs($this->user('compliance', 'read'))
            ->get(route('compliance.report.pdf', $inventory))
            ->assertOk();
    }

    public function test_user_without_compliance_access_is_denied(): void
    {
        $inventory = $this->makeInventory();

        // Q-008: other users are DENIED.
        $this->actingAs($this->user('staff', 'write'))
            ->get(route('compliance.report.pdf', $inventory))
            ->assertForbidden();

        $this->actingAs($this->user('security', 'read'))
            ->get(route('compliance.report.pdf', $inventory))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $inventory = $this->makeInventory();

        $this->get(route('compliance.report.pdf', $inventory))->assertRedirect(route('login'));
    }
}
