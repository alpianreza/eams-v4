<?php

namespace Tests\Feature\Notification;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\Notification;
use App\Models\User;
use App\Services\Checklist\WeeklyChecklistReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChecklistReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function picWithPendingChecklist(): array
    {
        $pic = User::factory()->create(['role' => 'compliance', 'permission' => 'write', 'status' => 'active', 'name' => 'Budi']);
        $category = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => 'daily']);
        $area = Area::create(['name' => 'A']);
        $inventory = ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);
        $inventory->pics()->sync([$pic->id]);

        return [$pic, $inventory];
    }

    public function test_pending_checklist_reminder_creates_in_app_notification(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00'); // working Tuesday
        [$pic] = $this->picWithPendingChecklist();

        $sent = app(WeeklyChecklistReminder::class)->send();

        $this->assertSame(1, $sent);
        $this->assertDatabaseHas('notifications', ['user_id' => $pic->id, 'type' => 'checklist_reminder']);
    }

    public function test_no_reminder_on_offday(): void
    {
        Carbon::setTestNow('2026-08-16 09:00:00'); // a Sunday (offday)
        $this->picWithPendingChecklist();

        // BR-23/24: never send reminders on an offday.
        $this->assertSame(0, app(WeeklyChecklistReminder::class)->send());
        $this->assertSame(0, Notification::count());
    }

    public function test_no_reminder_when_checklist_already_done(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        [$pic, $inventory] = $this->picWithPendingChecklist();

        // mark the current period done
        $inventory->load('itemType');
        \App\Models\ChecklistMaster::create(['asset_item_type_id' => $inventory->asset_item_type_id, 'question' => 'Q', 'frequency' => 'daily', 'active' => true]);
        \App\Models\ChecklistLog::create([
            'inventory_id' => $inventory->id, 'asset_item_type_id' => $inventory->asset_item_type_id,
            'checklist_master_id' => \App\Models\ChecklistMaster::first()->id, 'check_date' => '2026-08-18',
            'period_key' => '2026-08-18', 'status' => 'ok', 'checked_by_user_id' => $pic->id, 'checked_by_name' => 'Budi', 'mode' => 'standard',
        ]);

        $this->assertSame(0, app(WeeklyChecklistReminder::class)->send());
    }

    public function test_user_can_view_and_mark_notifications_read(): void
    {
        $user = User::factory()->create(['permission' => 'write']);
        $n = Notification::create(['user_id' => $user->id, 'title' => 'Test', 'type' => 'info']);

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Test');
        $this->actingAs($user)->post(route('notifications.read', $n));
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_user_cannot_mark_others_notification(): void
    {
        $user = User::factory()->create(['permission' => 'write']);
        $other = User::factory()->create();
        $n = Notification::create(['user_id' => $other->id, 'title' => 'X', 'type' => 'info']);

        $this->actingAs($user)->post(route('notifications.read', $n))->assertForbidden();
    }
}
