<?php

namespace Tests\Feature\Import;

use App\Models\Area;
use App\Models\ChecklistLog;
use App\Models\ComplianceInventory;
use App\Models\User;
use App\Services\Import\LegacyImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyImportTest extends TestCase
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

    protected function runImport(bool $dryRun = false): array
    {
        $report = (new LegacyImporter(dryRun: $dryRun))->run();
        $errors = collect($report)->flatMap(fn ($result, $table) => array_map(
            fn ($error) => "{$table}: {$error}",
            $result['errors']
        ))->values()->all();
        $this->assertSame([], $errors);

        return $report;
    }

    public function test_import_users_carries_and_maps_idempotently(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, email TEXT, password TEXT, photo TEXT, role TEXT, permission TEXT, page_access TEXT, status TEXT, wa_number TEXT)',
        ]);
        $this->legacy()->table('users')->insert([
            'id' => 1, 'username' => 'asep', 'name' => 'Asep', 'email' => 'asep@x.id', 'password' => '$2y$10$legacybcrypthash',
            'photo' => 'asep.jpg', 'role' => 'Compliance', 'permission' => 'write',
            'page_access' => '["home","compliance_inventory"]', 'status' => 'active', 'wa_number' => '0812',
        ]);

        $this->runImport();
        $this->runImport();

        $this->assertSame(1, User::where('username', 'asep')->count());
        $user = User::where('username', 'asep')->first();
        $this->assertSame('compliance', $user->role);
        $this->assertSame('$2y$10$legacybcrypthash', $user->password);
        $this->assertSame(['home', 'compliance_inventory'], $user->page_access);
        $this->assertSame('0812', $user->wa_number);
    }

    public function test_import_inventory_preserves_asset_code_and_maps_status(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE inventory_categories (id INTEGER PRIMARY KEY, name TEXT, code TEXT, active INTEGER)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY KEY, inventory_category_id INTEGER, code TEXT, name TEXT, checklist_frequency TEXT, allow_na INTEGER, active INTEGER)',
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, category_id INTEGER, item_type_id INTEGER, area_id INTEGER, asset_code TEXT, status TEXT, specific_area TEXT, qty INTEGER, active INTEGER)',
        ]);
        $this->legacy()->table('inventory_categories')->insert(['id' => 1, 'name' => 'Fire Safety', 'code' => 'FS', 'active' => 1]);
        $this->legacy()->table('asset_item_types')->insert(['id' => 1, 'inventory_category_id' => 1, 'code' => 'APAR', 'name' => 'APAR', 'checklist_frequency' => 'monthly', 'allow_na' => 0, 'active' => 1]);
        $this->legacy()->table('compliance_inventory')->insert([
            'id' => 1, 'category_id' => 1, 'item_type_id' => 1, 'area_id' => null,
            'asset_code' => 'APAR-001', 'status' => 'Need Repair', 'specific_area' => 'Lt. 1', 'qty' => 2, 'active' => 1,
        ]);

        $this->runImport();

        $inventory = ComplianceInventory::where('asset_code', 'APAR-001')->firstOrFail();
        $this->assertSame('need_repair', $inventory->status);
        $this->assertSame('Lt. 1', $inventory->specific_area);
        $this->assertNotNull(\App\Models\AssetItemType::where('code', 'APAR')->firstOrFail()->inventory_category_id);
        $this->assertNotNull($inventory->asset_item_type_id);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE areas (id INTEGER PRIMARY KEY, name TEXT, active INTEGER)',
        ]);
        $this->legacy()->table('areas')->insert(['id' => 1, 'name' => 'Gedung A', 'active' => 1]);

        $importer = new LegacyImporter(dryRun: true);
        $report = $importer->run();

        $this->assertSame([], $report['areas']['errors']);
        $this->assertTrue($importer->rolledBack);
        $this->assertSame(0, Area::count());
    }

    public function test_missing_legacy_tables_are_skipped_not_fatal(): void
    {
        $this->setUpLegacy([]);
        $report = $this->runImport();
        $this->assertTrue($report['users']['skipped'] ?? false);
    }

    protected function seedChecklistFixtures(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, role TEXT, permission TEXT, status TEXT)',
            'CREATE TABLE inventory_categories (id INTEGER PRIMARY KEY, name TEXT, code TEXT, active INTEGER)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY KEY, inventory_category_id INTEGER, code TEXT, name TEXT, checklist_frequency TEXT, allow_na INTEGER, active INTEGER)',
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, category_id INTEGER, item_type_id INTEGER, asset_code TEXT, status TEXT, active INTEGER)',
            'CREATE TABLE checklist_master (id INTEGER PRIMARY KEY, item_type_id INTEGER, question TEXT, frequency TEXT, require_photo INTEGER, active INTEGER)',
            'CREATE TABLE checklist_logs (id INTEGER PRIMARY KEY, inventory_id INTEGER, item_type_id INTEGER, checklist_template_id INTEGER, check_date TEXT, period_key TEXT, time_slot TEXT, status TEXT, remark TEXT, photo TEXT, checked_by TEXT, created_at TEXT, follow_up_status TEXT, follow_up_note TEXT, follow_up_date TEXT)',
        ]);
        $this->legacy()->table('users')->insert(['id' => 1, 'username' => 'budi', 'name' => 'Budi', 'role' => 'compliance', 'permission' => 'write', 'status' => 'active']);
        $this->legacy()->table('inventory_categories')->insert(['id' => 1, 'name' => 'FS', 'code' => 'FS', 'active' => 1]);
        $this->legacy()->table('asset_item_types')->insert(['id' => 1, 'inventory_category_id' => 1, 'code' => 'APAR', 'name' => 'APAR', 'checklist_frequency' => 'daily', 'allow_na' => 0, 'active' => 1]);
        $this->legacy()->table('compliance_inventory')->insert(['id' => 5, 'category_id' => 1, 'item_type_id' => 1, 'asset_code' => 'APAR-001', 'status' => 'Good', 'active' => 1]);
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
            'status' => '',
            'checked_by' => 'Budi',
            'created_at' => '2026-08-18 08:00:00',
            'follow_up_status' => 'open',
        ], $overrides));
    }

    public function test_checklist_log_maps_checked_by_to_user_and_snapshot(): void
    {
        $this->seedChecklistFixtures();
        $this->addLegacyLog();

        $this->runImport();

        $log = ChecklistLog::firstOrFail();
        $this->assertSame(1, $log->legacy_id);
        $this->assertSame('Budi', $log->checked_by_name);
        $this->assertNotNull($log->checked_by_user_id);
        $this->assertSame('2026-08-18', $log->period_key);
        $this->assertSame('ok', $log->status);
        $this->assertSame('2026-08-18', $log->check_date->format('Y-m-d'));
        $this->assertSame('open', $log->follow_up_status);
    }

    public function test_checklist_log_normalizes_status_and_derives_date(): void
    {
        $this->seedChecklistFixtures();
        $this->addLegacyLog([
            'check_date' => '0000-00-00',
            'period_key' => '2026-08-W2',
            'status' => 'ng',
        ]);

        $this->runImport();

        $log = ChecklistLog::firstOrFail();
        $this->assertSame('not_ok', $log->status);
        $this->assertSame('2026-08-01', $log->check_date->format('Y-m-d'));
    }

    public function test_full_dry_run_validates_relations_and_rolls_back_everything(): void
    {
        $this->seedChecklistFixtures();
        $this->addLegacyLog();

        $importer = new LegacyImporter(dryRun: true);
        $report = $importer->run();
        $errors = collect($report)->sum(fn ($result) => count($result['errors']));

        $this->assertSame(0, $errors);
        $this->assertTrue($importer->rolledBack);
        $this->assertSame(0, User::count());
        $this->assertSame(0, ComplianceInventory::count());
        $this->assertSame(0, ChecklistLog::count());
    }

    public function test_reimport_updates_same_log_and_preserves_history_and_native_rows(): void
    {
        $this->seedChecklistFixtures();
        $this->addLegacyLog();
        $this->runImport();

        $imported = ChecklistLog::where('legacy_id', 1)->firstOrFail();
        DB::table('checklist_log_histories')->insert([
            'checklist_log_id' => $imported->id,
            'changed_by_user_id' => $imported->checked_by_user_id,
            'changed_by_name' => 'Budi',
            'old_status' => 'ok',
            'new_status' => 'not_ok',
            'changed_at' => now(),
        ]);
        $native = ChecklistLog::create([
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

        $this->legacy()->table('checklist_logs')->where('id', 1)->update(['status' => 'ng']);
        $this->runImport();

        $this->assertSame($imported->id, ChecklistLog::where('legacy_id', 1)->value('id'));
        $this->assertSame('not_ok', ChecklistLog::where('legacy_id', 1)->value('status'));
        $this->assertTrue(ChecklistLog::whereKey($native->id)->exists());
        $this->assertDatabaseHas('checklist_log_histories', ['checklist_log_id' => $imported->id]);
        $this->assertSame(2, ChecklistLog::count());
    }

    public function test_checklist_import_flushes_more_than_one_chunk(): void
    {
        $this->seedChecklistFixtures();
        $rows = [];
        for ($id = 1; $id <= 1001; $id++) {
            $rows[] = [
                'id' => $id,
                'inventory_id' => 5,
                'item_type_id' => 1,
                'checklist_template_id' => 7,
                'check_date' => '2026-08-18',
                'period_key' => 'bulk-'.$id,
                'status' => 'ok',
                'checked_by' => 'Budi',
                'created_at' => '2026-08-18 08:00:00',
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            $this->legacy()->table('checklist_logs')->insert($chunk);
        }

        $this->runImport();

        $this->assertSame(1001, ChecklistLog::whereNotNull('legacy_id')->count());
    }
}
