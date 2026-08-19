<?php

namespace Tests\Feature\Monitoring;

use App\Models\Area;
use App\Models\AssetItemType;
use App\Models\ChecklistLog;
use App\Models\ChecklistMaster;
use App\Models\ComplianceInventory;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceRankingTest extends TestCase
{
    use RefreshDatabase;

    protected ?ComplianceInventory $inv = null;
    protected ?ChecklistMaster $q = null;
    protected ?AssetItemType $type = null;

    /** Create shared master data + inventory + question ONCE, then a log per call. */
    protected function makeLog(array $overrides = []): ChecklistLog
    {
        if (! $this->inv) {
            $cat = InventoryCategory::firstOrCreate(['code' => 'FS'], ['name' => 'Fire Safety']);
            $this->type = AssetItemType::firstOrCreate(['code' => 'APAR'], ['inventory_category_id' => $cat->id, 'name' => 'APAR', 'checklist_frequency' => 'daily']);
            $area = Area::firstOrCreate(['name' => 'A']);
            $this->inv = ComplianceInventory::create(['inventory_category_id' => $cat->id, 'asset_item_type_id' => $this->type->id, 'area_id' => $area->id, 'asset_code' => 'FS-APAR-001', 'status' => 'good', 'qty' => 1]);
            $this->q = ChecklistMaster::create(['asset_item_type_id' => $this->type->id, 'question' => 'Tekanan OK?', 'frequency' => 'daily', 'active' => true]);
        }

        return ChecklistLog::create(array_merge([
            'inventory_id' => $this->inv->id, 'asset_item_type_id' => $this->type->id, 'checklist_master_id' => $this->q->id,
            'check_date' => '2026-08-18', 'period_key' => '2026-08-18', 'status' => 'not_ok', 'remark' => 'Rusak',
            'checked_by_user_id' => null, 'checked_by_name' => 'Budi', 'mode' => 'standard', 'follow_up_status' => 'open',
        ], $overrides));
    }

    public function test_evidence_lists_not_ok_findings(): void
    {
        $this->makeLog();
        $user = User::factory()->create(['permission' => 'write']);

        $this->actingAs($user)->get(route('evidence.index'))->assertOk()->assertSee('Rusak');
    }

    public function test_follow_up_can_be_updated(): void
    {
        $log = $this->makeLog();
        $user = User::factory()->create(['permission' => 'write']);

        $this->actingAs($user)->put(route('evidence.followup', $log), [
            'follow_up_status' => 'monitoring', 'follow_up_note' => 'Sedang dipesan', 'follow_up_date' => '2026-08-25',
        ])->assertRedirect();

        $this->assertSame('monitoring', $log->fresh()->follow_up_status);
    }

    public function test_ranking_scores_ontime_10_late_3(): void
    {
        $user = User::factory()->create(['permission' => 'write', 'name' => 'Budi']);
        // on-time (check_date within the period) — period 2026-08-18
        $this->makeLog(['checked_by_user_id' => $user->id, 'checked_by_name' => 'Budi', 'status' => 'ok', 'check_date' => '2026-08-18', 'period_key' => '2026-08-18']);
        // late (check_date after the period end) — different period to avoid any dedup clash
        $this->makeLog(['checked_by_user_id' => $user->id, 'checked_by_name' => 'Budi', 'status' => 'ok', 'check_date' => '2026-08-30', 'period_key' => '2026-08-17']);

        $response = $this->actingAs($user)->get(route('ranking.index'))->assertOk()->assertSee('Budi');

        // 1 ontime (10) + 1 late (3) = 13
        $response->assertSee('13', false);
    }
}
