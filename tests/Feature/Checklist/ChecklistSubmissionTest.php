<?php

namespace Tests\Feature\Checklist;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChecklistSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function checker(): User
    {
        return User::factory()->create(['role' => 'compliance', 'permission' => 'write', 'name' => 'Budi']);
    }

    protected function makeInventory(bool $allowNa = false, string $frequency = 'daily'): ComplianceInventory
    {
        $category = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        $itemType = AssetItemType::create(['inventory_category_id' => $category->id, 'name' => 'APAR', 'code' => 'APAR', 'checklist_frequency' => $frequency, 'allow_na' => $allowNa]);
        $area = Area::create(['name' => 'A']);

        return ComplianceInventory::create([
            'inventory_category_id' => $category->id, 'asset_item_type_id' => $itemType->id,
            'area_id' => $area->id, 'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1,
        ]);
    }

    protected function question(ComplianceInventory $inventory, bool $requirePhoto = false): ChecklistMaster
    {
        return ChecklistMaster::create([
            'asset_item_type_id' => $inventory->asset_item_type_id,
            'question' => 'Tekanan normal?',
            'frequency' => $inventory->itemType->checklist_frequency,
            'require_photo' => $requirePhoto,
            'active' => true,
        ]);
    }

    public function test_standard_submit_creates_logs_with_period_key_and_checker_snapshot(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00'); // a working Tuesday
        $inventory = $this->makeInventory();
        $q = $this->question($inventory);

        $this->actingAs($this->checker())->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'ok',
            "remark_{$q->id}" => 'Baik',
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_logs', [
            'inventory_id' => $inventory->id,
            'checklist_master_id' => $q->id,
            'period_key' => '2026-08-18',
            'status' => 'ok',
            'checked_by_name' => 'Budi',   // Q-006 snapshot
            'mode' => 'standard',
        ]);
        $this->assertNotNull(ChecklistLog::first()->checked_by_user_id);
    }

    public function test_not_ok_requires_remark_or_photo_in_standard_mode(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        $inventory = $this->makeInventory();
        $q = $this->question($inventory);

        // NOT_OK with neither remark nor photo → rejected (Q-013)
        $this->actingAs($this->checker())->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'not_ok',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('checklist_logs', 0);
    }

    public function test_not_ok_with_remark_is_accepted(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        $inventory = $this->makeInventory();
        $q = $this->question($inventory);

        $this->actingAs($this->checker())->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'not_ok',
            "remark_{$q->id}" => 'Tekanan turun, perlu refill',
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_logs', ['status' => 'not_ok', 'remark' => 'Tekanan turun, perlu refill']);
    }

    public function test_na_rejected_when_item_type_disallows_it(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        $inventory = $this->makeInventory(allowNa: false);
        $q = $this->question($inventory);

        // Q-001: NA invalid when allow_na=false
        $this->actingAs($this->checker())->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'na',
        ])->assertSessionHasErrors('status');
    }

    public function test_na_accepted_when_item_type_allows_it(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        $inventory = $this->makeInventory(allowNa: true);
        $q = $this->question($inventory);

        $this->actingAs($this->checker())->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'na',
        ])->assertRedirect();

        // Q-001: NA is a valid result (not failure / not pending).
        $this->assertDatabaseHas('checklist_logs', ['status' => 'na']);
    }

    public function test_daily_entry_is_blocked_on_offday(): void
    {
        Carbon::setTestNow('2026-08-16 09:00:00'); // a Sunday
        $inventory = $this->makeInventory(frequency: 'daily');
        $q = $this->question($inventory);

        // BR-08: daily entry blocked on offday
        $this->actingAs($this->checker())->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'ok',
        ])->assertSessionHasErrors('checklist');

        $this->assertDatabaseCount('checklist_logs', 0);
    }

    public function test_resubmit_updates_and_writes_history(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        $inventory = $this->makeInventory();
        $q = $this->question($inventory);
        $checker = $this->checker();

        // first submit
        $this->actingAs($checker)->post(route('compliance.checklist.store', $inventory), ["status_{$q->id}" => 'ok']);
        // correct it (BR-09 dedup: same inventory+period+question → update, not duplicate)
        $this->actingAs($checker)->post(route('compliance.checklist.store', $inventory), [
            "status_{$q->id}" => 'not_ok', "remark_{$q->id}" => 'Koreksi: ada masalah',
        ]);

        // one row only (dedup), updated
        $this->assertSame(1, ChecklistLog::where('checklist_master_id', $q->id)->count());
        $this->assertDatabaseHas('checklist_logs', ['checklist_master_id' => $q->id, 'status' => 'not_ok']);

        // Q-023: a history row captured old→new
        $this->assertDatabaseHas('checklist_log_histories', [
            'old_status' => 'ok',
            'new_status' => 'not_ok',
            'changed_by_name' => 'Budi',
        ]);
    }
}
