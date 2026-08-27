<?php

namespace Tests\Feature\Import;

use App\Models\ChecklistLog;
use App\Services\Import\LegacyImporter;
use App\Services\Import\LegacyReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyReconcileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpLegacy(array $tables): void
    {
        config()->set('database.connections.legacy', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('legacy');
        foreach ($tables as $ddl) {
            DB::connection('legacy')->statement($ddl);
        }
    }

    protected function legacy(): \Illuminate\Database\Connection
    {
        return DB::connection('legacy');
    }

    protected function seedLegacy(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, role TEXT, permission TEXT, status TEXT)',
            'CREATE TABLE areas (id INTEGER PRIMARY KEY, name TEXT, active INTEGER)',
            'CREATE TABLE inventory_categories (id INTEGER PRIMARY KEY, name TEXT, code TEXT, active INTEGER)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY KEY, inventory_category_id INTEGER, code TEXT, name TEXT, checklist_frequency TEXT, allow_na INTEGER, active INTEGER)',
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, category_id INTEGER, item_type_id INTEGER, area_id INTEGER, asset_code TEXT, status TEXT, active INTEGER)',
            'CREATE TABLE checklist_master (id INTEGER PRIMARY KEY, item_type_id INTEGER, question TEXT, frequency TEXT, require_photo INTEGER, active INTEGER)',
            'CREATE TABLE checklist_logs (id INTEGER PRIMARY KEY, inventory_id INTEGER, item_type_id INTEGER, checklist_template_id INTEGER, check_date TEXT, period_key TEXT, time_slot TEXT, status TEXT, remark TEXT, photo TEXT, checked_by TEXT, created_at TEXT, follow_up_status TEXT, follow_up_note TEXT, follow_up_date TEXT)',
        ]);

        $this->legacy()->table('users')->insert(['id' => 1, 'username' => 'budi', 'name' => 'Budi', 'role' => 'compliance', 'permission' => 'write', 'status' => 'active']);
        $this->legacy()->table('areas')->insert(['id' => 1, 'name' => 'Gedung A', 'active' => 1]);
        $this->legacy()->table('inventory_categories')->insert(['id' => 1, 'name' => 'Fire Safety', 'code' => 'FS', 'active' => 1]);
        $this->legacy()->table('asset_item_types')->insert(['id' => 1, 'inventory_category_id' => 1, 'code' => 'APAR', 'name' => 'APAR', 'checklist_frequency' => 'daily', 'allow_na' => 0, 'active' => 1]);
        $this->legacy()->table('compliance_inventory')->insert(['id' => 5, 'category_id' => 1, 'item_type_id' => 1, 'area_id' => 1, 'asset_code' => 'APAR-001', 'status' => 'Good', 'active' => 1]);
        $this->legacy()->table('checklist_master')->insert(['id' => 7, 'item_type_id' => 1, 'question' => 'Tekanan?', 'frequency' => 'daily', 'require_photo' => 0, 'active' => 1]);
    }

    protected function addLegacyLog(array $overrides = []): void
    {
        $this->legacy()->table('checklist_logs')->insert(array_merge([
            'id' => 1,
            'inventory_id' => 5,
            'item_type_id' => 1,
            'checklist_template_id' => 7,
            'check_date' => '2026-08-18',
            'period_key' => '2026-08-18',
            'status' => 'ok',
            'checked_by' => 'Budi',
            'created_at' => '2026-08-18 08:00:00',
        ], $overrides));
    }

    public function test_reconcile_is_clean_after_a_successful_import(): void
    {
        $this->seedLegacy();
        $this->addLegacyLog();

        (new LegacyImporter)->run();

        $report = (new LegacyReconciler)->reconcile();

        $this->assertSame([], $report['issues']);
        $this->assertTrue($report['ok']);
        $this->assertSame(1, $report['checklist_log_parity']['legacy_rows']);
        $this->assertSame(1, $report['checklist_log_parity']['imported_rows']);
        $this->assertSame(0, $report['checklist_log_parity']['missing_in_target']);
        $this->assertSame(0, $report['checklist_log_parity']['extra_in_target']);
    }

    public function test_reconcile_reports_rows_that_never_reached_the_target(): void
    {
        $this->seedLegacy();
        $this->addLegacyLog();

        $report = (new LegacyReconciler)->reconcile();

        $this->assertFalse($report['ok']);
        $this->assertSame(1, $report['checklist_log_parity']['legacy_rows']);
        $this->assertSame(0, $report['checklist_log_parity']['imported_rows']);
        $this->assertSame(1, $report['checklist_log_parity']['missing_in_target']);
        $this->assertSame([1], $report['checklist_log_parity']['missing_sample_legacy_ids']);
        $this->assertTrue(collect($report['issues'])->contains(
            fn (string $issue): bool => str_contains($issue, 'compliance_inventory -> compliance_inventories')
        ));
    }

    public function test_reconcile_flags_values_the_importer_normalizes_or_skips(): void
    {
        $this->seedLegacy();
        $this->addLegacyLog(['id' => 350, 'status' => '', 'checked_by' => 'Welda Bachtiar']);
        $this->addLegacyLog(['id' => 351, 'inventory_id' => 999, 'check_date' => '0000-00-00', 'period_key' => '']);
        $this->legacy()->table('areas')->insert(['id' => 2, 'name' => 'Gedung A', 'active' => 1]);

        $report = (new LegacyReconciler)->reconcile();

        $normalization = collect($report['legacy_normalizations'])->first(
            fn (array $row): bool => $row['table'] === 'checklist_logs' && $row['column'] === 'status'
        );
        $this->assertNotNull($normalization);
        $this->assertSame('ok', $normalization['normalized_to']);
        $this->assertSame(1, $normalization['count']);
        $this->assertSame(350, (int) $normalization['samples'][0]['id']);

        $orphan = collect($report['legacy_orphans'])->first(
            fn (array $row): bool => $row['table'] === 'checklist_logs' && $row['column'] === 'inventory_id'
        );
        $this->assertNotNull($orphan);
        $this->assertSame([351], array_map('intval', $orphan['sample_ids']));

        $duplicate = collect($report['legacy_duplicates'])->first(
            fn (array $row): bool => $row['table'] === 'areas' && $row['column'] === 'name'
        );
        $this->assertNotNull($duplicate);
        $this->assertSame(1, $duplicate['groups']);

        $this->assertSame(1, $report['checklist_log_parity']['unresolvable_dates']);
        $this->assertSame(351, (int) $report['checklist_log_parity']['unresolvable_date_samples'][0]['id']);
        $this->assertFalse($report['ok']);
    }

    public function test_reconcile_ignores_rows_created_inside_the_app(): void
    {
        $this->seedLegacy();
        $this->addLegacyLog();
        (new LegacyImporter)->run();

        $imported = ChecklistLog::whereNotNull('legacy_id')->firstOrFail();
        ChecklistLog::create([
            'inventory_id' => $imported->inventory_id,
            'asset_item_type_id' => $imported->asset_item_type_id,
            'checklist_master_id' => $imported->checklist_master_id,
            'check_date' => '2026-08-19',
            'period_key' => '2026-08-19',
            'status' => 'ok',
            'checked_by_user_id' => $imported->checked_by_user_id,
            'checked_by_name' => 'Budi',
            'mode' => 'standard',
        ]);

        $report = (new LegacyReconciler)->reconcile();

        $this->assertSame([], $report['issues']);
        $this->assertSame(1, $report['checklist_log_parity']['app_created_rows']);
        $this->assertSame(1, $report['checklist_log_parity']['imported_rows']);
        $this->assertSame(0, $report['checklist_log_parity']['extra_in_target']);
    }

    public function test_reconcile_detects_target_rows_whose_legacy_source_disappeared(): void
    {
        $this->seedLegacy();
        $this->addLegacyLog();
        (new LegacyImporter)->run();

        $this->legacy()->table('checklist_logs')->where('id', 1)->delete();

        $report = (new LegacyReconciler)->reconcile();

        $this->assertSame(1, $report['checklist_log_parity']['extra_in_target']);
        $this->assertSame([1], array_map('intval', $report['checklist_log_parity']['extra_sample_legacy_ids']));
        $this->assertFalse($report['ok']);
    }

    public function test_command_exit_code_reflects_findings(): void
    {
        $this->seedLegacy();
        $this->addLegacyLog();

        $this->artisan('eams:reconcile --samples=3')->assertExitCode(1);

        (new LegacyImporter)->run();

        $this->artisan('eams:reconcile')->assertExitCode(0);
    }
}
