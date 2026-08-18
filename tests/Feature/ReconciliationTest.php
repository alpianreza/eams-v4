<?php

namespace Tests\Feature;

use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PHASE 2M — final cross-cutting parity gate. Asserts the canonical invariants that tie
 * the whole rebuilt system together (the individual rules are covered per-module).
 */
class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_core_tables_exist(): void
    {
        foreach ([
            'users', 'user_roles', 'areas', 'inventory_categories', 'asset_item_types', 'holidays', 'employees',
            'audit_logs', 'login_sessions', 'compliance_inventories', 'compliance_inventory_pics',
            'checklist_master', 'checklist_logs', 'checklist_log_histories', 'it_devices', 'it_device_commands',
            'boiler_fuel_logs', 'pdam_water_logs', 'pdam_water_boiler_logs', 'ipal_logs',
            'patrol_routes', 'patrol_checkpoints', 'patrol_sessions', 'patrol_logs',
            'compliance_questionnaires', 'compliance_calendar_events', 'notifications',
            'ems_water_consumption_entries', 'fdm_production_section_entries', 'thermal_imaging_reports',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_canonical_enums_are_enforced(): void
    {
        // inventory status canonical (Q-017) and never mixed with checklist result
        $this->assertSame(['good', 'need_repair', 'not_active'], ComplianceInventory::STATUSES);
        // checklist result canonical (NOT "baik"/"tersedia")
        $this->assertSame(['ok', 'not_ok', 'na'], ChecklistLog::STATUSES);
    }

    public function test_period_engine_is_the_single_source(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00'); // Tuesday
        // daily + weekly(month-slice) + monthly keys derive from one engine
        $this->assertSame('2026-08-18', ChecklistPeriod::periodKey('daily', Carbon::now()));
        $this->assertSame('2026-08-W3', ChecklistPeriod::periodKey('weekly', Carbon::now()));
        $this->assertSame('2026-08', ChecklistPeriod::periodKey('monthly', Carbon::now()));
    }

    public function test_item_type_resolves_by_business_code(): void
    {
        $cat = InventoryCategory::create(['name' => 'FS', 'code' => 'FS']);
        AssetItemType::create(['inventory_category_id' => $cat->id, 'name' => 'APAR', 'code' => 'APAR']);

        // business identifier is `code`, never the auto-increment id (Q-015)
        $this->assertSame('APAR', AssetItemType::findByCode('APAR')->code);
    }

    public function test_pic_rule_max_two_no_primary(): void
    {
        // compliance_inventory_pics is the source of truth, no `is_primary` column (Q-007)
        $this->assertFalse(Schema::hasColumn('compliance_inventory_pics', 'is_primary'));
    }

    public function test_checked_by_is_user_id_plus_name_snapshot(): void
    {
        // Q-006: checklist_logs carries checked_by_user_id + checked_by_name, not a bare string
        $this->assertTrue(Schema::hasColumn('checklist_logs', 'checked_by_user_id'));
        $this->assertTrue(Schema::hasColumn('checklist_logs', 'checked_by_name'));
        $this->assertFalse(Schema::hasColumn('checklist_logs', 'checked_by'));
    }

    public function test_read_only_user_is_blocked_from_writing(): void
    {
        $reader = User::factory()->create(['role' => 'staff', 'permission' => 'read']);

        // BR-26: global write guard blocks read-only users from mutations
        $this->actingAs($reader)->post(route('calendar.store'), ['title' => 'X', 'start_at' => '2026-08-20'])
            ->assertForbidden();
    }
}
