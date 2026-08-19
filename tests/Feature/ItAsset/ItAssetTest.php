<?php

namespace Tests\Feature\ItAsset;

use App\Models\Employee;
use App\Models\ItAsset\Asset;
use App\Models\ItAsset\AssetAssignment;
use App\Models\ItAsset\AssetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    protected function makeAsset(): Asset
    {
        $cat = AssetCategory::create(['category_name' => 'IT', 'sub_category' => 'Laptop']);

        return Asset::create(['inventory_no' => 'IT-LAP-001', 'category_id' => $cat->id, 'asset_name' => 'Laptop A', 'status' => 'aktif']);
    }

    public function test_assign_closes_active_and_creates_new(): void
    {
        $asset = $this->makeAsset();
        $e1 = Employee::create(['employee_id' => 'E1', 'name' => 'Asep', 'division' => 'IT', 'position' => 'Staff', 'status' => 'active']);
        $e2 = Employee::create(['employee_id' => 'E2', 'name' => 'Budi', 'division' => 'IT', 'position' => 'Staff', 'status' => 'active']);

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('it-assets.assign', $asset), ['employee_id' => $e1->id]);
        $this->actingAs($admin)->post(route('it-assets.assign', $asset), ['employee_id' => $e2->id]);

        // BR-31: first assignment returned, second active
        $this->assertNotNull(AssetAssignment::where('employee_id', $e1->id)->first()->returned_at);
        $this->assertNull(AssetAssignment::where('employee_id', $e2->id)->first()->returned_at);
        $this->assertSame(1, AssetAssignment::where('asset_id', $asset->id)->whereNull('returned_at')->count());
    }

    public function test_cannot_assign_to_inactive_employee(): void
    {
        $asset = $this->makeAsset();
        $inactive = Employee::create(['employee_id' => 'E9', 'name' => 'Nonaktif', 'division' => 'IT', 'position' => 'Staff', 'status' => 'inactive']);

        $this->actingAs($this->admin())->post(route('it-assets.assign', $asset), ['employee_id' => $inactive->id])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_status_rusak_auto_returns_active_assignment(): void
    {
        $asset = $this->makeAsset();
        $e1 = Employee::create(['employee_id' => 'E1', 'name' => 'Asep', 'division' => 'IT', 'position' => 'Staff', 'status' => 'active']);
        $asset->assignments()->create(['employee_id' => $e1->id, 'assigned_at' => now(), 'returned_at' => null]);

        $this->actingAs($this->admin())->put(route('it-assets.update', $asset), ['status' => 'rusak'])->assertRedirect();

        // BR-31: rusak → active assignment auto-returned
        $this->assertSame(0, AssetAssignment::where('asset_id', $asset->id)->whereNull('returned_at')->count());
    }

    public function test_employee_with_active_assignment_cannot_be_deleted(): void
    {
        $asset = $this->makeAsset();
        $e1 = Employee::create(['employee_id' => 'E1', 'name' => 'Asep', 'division' => 'IT', 'position' => 'Staff', 'status' => 'active']);
        $asset->assignments()->create(['employee_id' => $e1->id, 'assigned_at' => now(), 'returned_at' => null]);

        $this->actingAs($this->admin())->delete(route('master-data.employees.destroy', $e1))
            ->assertSessionHasErrors('employee'); // BR-32
        $this->assertDatabaseHas('employees', ['id' => $e1->id]);
    }
}
