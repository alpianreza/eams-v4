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

    public function test_progress_page_lists_pic_rows_sorted_with_monthly_aggregates(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00'); // Wednesday, working day
        $pic = User::factory()->create(['permission' => 'write', 'name' => 'Budi Santoso', 'status' => 'active']);
        $this->makeInventory([$pic->id]);

        // A user without PIC inventories is skipped (legacy behavior).
        $bystander = User::factory()->create(['permission' => 'write', 'name' => 'Cici', 'status' => 'active']);

        $this->actingAs($bystander)->get(route('progress.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('data-eams-progress-row="' . $bystander->id . '"', false);
    }

    public function test_progress_counts_done_after_log_posted_via_standard_channel(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $pic = User::factory()->create(['permission' => 'write', 'name' => 'Budi', 'status' => 'active']);
        $inv = $this->makeInventory([$pic->id]);
        $q = ChecklistMaster::where('asset_item_type_id', $inv->asset_item_type_id)->first();

        // done=1 of the working days so far in August 2026 (13 non-offday days up to the 19th) => 8%
        $this->actingAs($pic)->post(route('compliance.checklist.store', $inv), ["status_{$q->id}" => 'ok']);

        $this->actingAs($pic)->get(route('progress.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('8%');
    }

    public function test_progress_export_downloads_csv(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $pic = User::factory()->create(['permission' => 'write', 'name' => 'Budi', 'status' => 'active']);
        $inv = $this->makeInventory([$pic->id]);
        $q = ChecklistMaster::where('asset_item_type_id', $inv->asset_item_type_id)->first();
        $this->actingAs($pic)->post(route('compliance.checklist.store', $inv), ["status_{$q->id}" => 'ok']);

        $this->actingAs($pic)->get(route('progress.export', ['month' => '2026-08']))
            ->assertOk()
            ->assertDownload('progress-2026-08.csv')
            ->assertSee('Budi');
    }

    public function test_remind_creates_in_app_notification_for_pic(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $admin = User::factory()->create(['role' => 'admin', 'permission' => 'write']);
        $pic = User::factory()->create(['permission' => 'write', 'name' => 'Budi', 'status' => 'active']);
        $this->makeInventory([$pic->id]); // no logs -> pending exists

        $this->actingAs($admin)->post(route('progress.remind', $pic), ['month' => '2026-08'])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $pic->id,
            'type' => 'warning',
        ]);
    }

    public function test_remind_forbidden_for_read_only_user(): void
    {
        $reader = User::factory()->create(['role' => 'compliance', 'permission' => 'read']);
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($reader)->post(route('progress.remind', $target), ['month' => '2026-08'])
            ->assertForbidden();
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

        // the PIC has a pending daily checklist today -> appears on home
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
