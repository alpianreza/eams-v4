<?php

namespace Tests\Feature;

use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\Area;
use App\Models\User;
use App\Actions\Checklist\SaveGridChecklist;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Phase-2 human decisions (2026-08-19): Q-011, Q-019, Q-021, Q-024, Q-025. */
class Phase2DecisionsTest extends TestCase
{
    use RefreshDatabase;

    // Q-011: weekly backfill ≤3 bulan grace; monthly unlimited.
    public function test_weekly_backfill_has_three_month_grace_but_monthly_unlimited(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $old = Carbon::parse('2026-01-05'); // ~7 months ago

        $this->assertFalse(ChecklistPeriod::isEditable('weekly', $old));   // weekly: beyond 3-month grace → locked
        $this->assertTrue(ChecklistPeriod::isEditable('monthly', $old));   // monthly: unlimited backfill
        $this->assertTrue(ChecklistPeriod::isEditable('weekly', Carbon::now())); // current weekly editable
    }

    // Q-021: read-only user MAY change their own password (self-service whitelisted).
    public function test_read_only_user_can_change_own_password(): void
    {
        $reader = User::factory()->create(['permission' => 'read', 'password' => 'oldpass123']);

        $this->actingAs($reader)->post(route('self.password.update'), [
            'current_password' => 'oldpass123',
            'password' => 'newpass456',
            'password_confirmation' => 'newpass456',
        ])->assertRedirect(); // NOT 403 — whitelisted self-service

        $this->assertTrue(Hash::check('newpass456', $reader->fresh()->password));
    }

    // Q-021: but read-only users stay blocked from all OTHER mutations.
    public function test_read_only_user_still_blocked_from_other_writes(): void
    {
        $reader = User::factory()->create(['permission' => 'read']);

        $this->actingAs($reader)->post(route('calendar.store'), ['title' => 'X', 'start_at' => '2026-08-20'])
            ->assertForbidden();
    }

    // Q-024: mark-all skips existing cells (no overwrite), uniformly.
    public function test_mark_all_skips_existing_cells(): void
    {
        Carbon::setTestNow('2026-08-19 09:00:00');
        $user = User::factory()->create(['permission' => 'write']);
        $cat = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        $type = AssetItemType::create(['inventory_category_id' => $cat->id, 'name' => 'Heat Detector', 'code' => 'HEAT', 'checklist_frequency' => 'daily']);
        $area = Area::create(['name' => 'A']);
        $inv = ComplianceInventory::create(['inventory_category_id' => $cat->id, 'asset_item_type_id' => $type->id, 'area_id' => $area->id, 'asset_code' => 'FS-HEAT-001', 'status' => 'good', 'qty' => 1]);
        $q = ChecklistMaster::create(['asset_item_type_id' => $type->id, 'question' => 'OK?', 'frequency' => 'daily', 'active' => true]);

        // pre-existing cell = not_ok
        ChecklistLog::create(['inventory_id' => $inv->id, 'asset_item_type_id' => $type->id, 'checklist_master_id' => $q->id, 'check_date' => '2026-08-19', 'period_key' => '2026-08-19', 'status' => 'not_ok', 'remark' => 'ada', 'checked_by_user_id' => $user->id, 'checked_by_name' => $user->name, 'mode' => 'grid']);

        SaveGridChecklist::markAll($type, 'ok', $user);

        // existing not_ok cell NOT overwritten to ok (Q-024: uniform skip)
        $this->assertSame('not_ok', ChecklistLog::where('checklist_master_id', $q->id)->first()->status);
    }

    // Q-025: agent API accepts GET (field-agent compatibility).
    public function test_agent_api_accepts_get_heartbeat(): void
    {
        $this->get('/api/agent/heartbeat?device_token=DEV-GET-1&hostname=PC-1')->assertOk(); // not 405
        $this->assertDatabaseHas('it_devices', ['device_token' => 'DEV-GET-1']);
    }
}
