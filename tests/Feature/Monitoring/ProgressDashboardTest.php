<?php

namespace Tests\Feature\Monitoring;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProgressDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInventory(array $picIds = []): ComplianceInventory
    {
        $cat = InventoryCategory::firstOrCreate(['code' => 'FS'], ['name' => 'Fire Safety']);
        $type = AssetItemType::firstOrCreate(['code' => 'APAR'], ['inventory_category_id' => $cat->id, 'name' => 'APAR', 'checklist_frequency' => 'daily']);
        $area = Area::firstOrCreate(['name' => 'A']);
        $inv = ComplianceInventory::create(['inventory_category_id' => $cat->id, 'asset_item_type_id' => $type->id, 'area_id' => $area->id, 'asset_code' => 'FS-APAR-'.uniqid(), 'status' => 'good', 'qty' => 1]);
        ChecklistMaster::firstOrCreate(['asset_item_type_id' => $type->id, 'question' => 'Q'], ['frequency' => 'daily', 'active' => true]);
        if ($picIds) {
            $inv->pics()->sync($picIds);
        }

        return $inv;
    }

    public function test_progress_shows_current_period_status(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00'); // a Wednesday (working day)
        $inv = $this->makeInventory();
        $user = User::factory()->create(['permission' => 'write']);

        // no log today → OPEN
        $this->actingAs($user)->get(route('progress.index'))->assertOk()->assertSee($inv->asset_code);
    }

    public function test_dashboard_kpi_counts(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $this->makeInventory();
        $user = User::factory()->create(['permission' => 'write']);

        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()
            ->assertSee('Inventory aktif')->assertSee('Checklist open');
    }

    public function test_home_shows_personal_pending_tasks_for_pic(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $pic = User::factory()->create(['permission' => 'write', 'name' => 'Budi', 'status' => 'active']);
        $inv = $this->makeInventory([$pic->id]);

        // the PIC has a pending daily checklist today → appears on home
        $this->actingAs($pic)->get(route('home'))->assertOk()->assertSee($inv->asset_code);
    }

    public function test_home_shows_unread_notification_count(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $user = User::factory()->create(['permission' => 'write', 'status' => 'active']);
        Notification::create(['user_id' => $user->id, 'title' => 'Test', 'type' => 'info']);
        Notification::create(['user_id' => $user->id, 'title' => 'Test 2', 'type' => 'info']);

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('2');
    }
}
