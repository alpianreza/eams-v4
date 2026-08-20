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

    /** Run the importer directly and fail loudly with the real per-table errors. */
    protected function runImport(bool $dryRun = false): array
    {
        $report = (new LegacyImporter(dryRun: $dryRun))->run();
        $errors = collect($report)->flatMap(fn ($r, $k) => array_map(fn ($e) => "$k: $e", $r['errors']))->values()->all();
        $this->assertSame([], $errors); // surfaces the real import error(s) on failure

        return $report;
    }

    public function test_import_users_carries_and_maps_idempotently(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, email TEXT, password TEXT, role TEXT, permission TEXT, status TEXT)',
        ]);
        $this->legacy()->table('users')->insert([
            'id' => 1, 'username' => 'asep', 'name' => 'Asep', 'email' => 'asep@x.id', 'password' => '$2y$10$legacybcrypthash', 'role' => 'Compliance', 'permission' => 'write', 'status' => 'active',
        ]);

        $this->runImport();
        $this->runImport(); // re-run → idempotent

        $this->assertSame(1, User::where('username', 'asep')->count());
        $user = User::where('username', 'asep')->first();
        $this->assertSame('compliance', $user->role);
        // CARRY: legacy hash preserved exactly (NOT re-hashed by the 'hashed' cast).
        $this->assertSame('$2y$10$legacybcrypthash', $user->password);
    }

    public function test_import_inventory_preserves_asset_code_and_maps_status(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE inventory_categories (id INTEGER PRIMARY KEY, name TEXT, code TEXT, active INTEGER)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY KEY, category_id INTEGER, code TEXT, name TEXT, checklist_frequency TEXT, allow_na INTEGER, active INTEGER)',
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, category_id INTEGER, item_type_id INTEGER, area_id INTEGER, asset_code TEXT, status TEXT, specific_area TEXT, qty INTEGER, active INTEGER)',
        ]);
        $this->legacy()->table('inventory_categories')->insert(['id' => 1, 'name' => 'Fire Safety', 'code' => 'FS', 'active' => 1]);
        $this->legacy()->table('asset_item_types')->insert(['id' => 1, 'category_id' => 1, 'code' => 'APAR', 'name' => 'APAR', 'checklist_frequency' => 'monthly', 'allow_na' => 0, 'active' => 1]);
        $this->legacy()->table('compliance_inventory')->insert([
            'id' => 1, 'category_id' => 1, 'item_type_id' => 1, 'area_id' => null,
            'asset_code' => 'APAR-001', 'status' => 'Need Repair', 'specific_area' => 'Lt. 1', 'qty' => 2, 'active' => 1,
        ]);

        $this->runImport();

        $inv = ComplianceInventory::where('asset_code', 'APAR-001')->firstOrFail();  // Q-020: exact
        $this->assertSame('need_repair', $inv->status);   // Q-017 transform
        $this->assertSame('Lt. 1', $inv->specific_area);
        $this->assertNotNull($inv->asset_item_type_id);   // FK resolved via legacy→new id map
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE areas (id INTEGER PRIMARY KEY, name TEXT, active INTEGER)',
        ]);
        $this->legacy()->table('areas')->insert(['id' => 1, 'name' => 'Gedung A', 'active' => 1]);

        $this->runImport(dryRun: true);

        $this->assertSame(0, Area::count());
    }

    public function test_missing_legacy_tables_are_skipped_not_fatal(): void
    {
        $this->setUpLegacy([]);
        $report = $this->runImport();  // should run with no errors; tables skipped
        $this->assertTrue($report['users']['skipped'] ?? false);
    }

    /** Skema legacy ASLI: checklist_logs memakai kolom checklist_template_id (bukan checklist_master_id). */
    protected function seedChecklistFixtures(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, role TEXT, permission TEXT, status TEXT)',
            'CREATE TABLE inventory_categories (id INTEGER PRIMARY KEY, name TEXT, code TEXT, active INTEGER)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY KEY, category_id INTEGER, code TEXT, name TEXT, checklist_frequency TEXT, allow_na INTEGER, active INTEGER)',
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, category_id INTEGER, item_type_id INTEGER, asset_code TEXT, status TEXT, active INTEGER)',
            'CREATE TABLE checklist_master (id INTEGER PRIMARY KEY, item_type_id INTEGER, question TEXT, frequency TEXT, require_photo INTEGER, active INTEGER)',
            // Kolom legacy asli: checklist_template_id, check_date, follow_up_status/note/date.
            'CREATE TABLE checklist_logs (id INTEGER PRIMARY KEY, inventory_id INTEGER, item_type_id INTEGER, checklist_template_id INTEGER, check_date TEXT, period_key TEXT, time_slot TEXT, status TEXT, remark TEXT, photo TEXT, checked_by TEXT, created_at TEXT, follow_up_status TEXT, follow_up_note TEXT, follow_up_date TEXT)',
        ]);
        $this->legacy()->table('users')->insert(['id' => 1, 'username' => 'budi', 'name' => 'Budi', 'role' => 'compliance', 'permission' => 'write', 'status' => 'active']);
        $this->legacy()->table('inventory_categories')->insert(['id' => 1, 'name' => 'FS', 'code' => 'FS', 'active' => 1]);
        $this->legacy()->table('asset_item_types')->insert(['id' => 1, 'category_id' => 1, 'code' => 'APAR', 'name' => 'APAR', 'checklist_frequency' => 'daily', 'allow_na' => 0, 'active' => 1]);
        $this->legacy()->table('compliance_inventory')->insert(['id' => 5, 'category_id' => 1, 'item_type_id' => 1, 'asset_code' => 'APAR-001', 'status' => 'Good', 'active' => 1]);
        $this->legacy()->table('checklist_master')->insert(['id' => 7, 'item_type_id' => 1, 'question' => 'Tekanan?', 'frequency' => 'daily', 'require_photo' => 0, 'active' => 1]);
    }

    public function test_checklist_log_maps_checked_by_to_user_and_snapshot(): void
    {
        $this->seedChecklistFixtures();
        // legacy pakai checklist_template_id=7 (→ checklist_master.id 7); status kosong '' → ok; follow_up 'open' ikut terbawa.
        $this->legacy()->table('checklist_logs')->insert(['inventory_id' => 5, 'item_type_id' => 1, 'checklist_template_id' => 7, 'check_date' => '2026-08-18', 'period_key' => '2026-08-18', 'status' => '', 'checked_by' => 'Budi', 'created_at' => '2026-08-18 08:00:00', 'follow_up_status' => 'open']);

        $this->runImport();

        $log = ChecklistLog::firstOrFail();
        $this->assertSame('Budi', $log->checked_by_name);   // Q-006 snapshot
        $this->assertNotNull($log->checked_by_user_id);    // resolved to the user
        $this->assertSame('2026-08-18', $log->period_key);
        $this->assertSame('ok', $log->status);             // '' → ok
        $this->assertSame('2026-08-18', $log->check_date->format('Y-m-d'));
        $this->assertSame('open', $log->follow_up_status); // follow_up preserved
    }

    public function test_checklist_log_normalizes_status_and_derives_date(): void
    {
        $this->seedChecklistFixtures();
        // 'ng' → not_ok (BR-11); weekly period_key 'YYYY-MM-Wn' → derive check_date ke tanggal 1 bulan itu.
        $this->legacy()->table('checklist_logs')->insert(['inventory_id' => 5, 'item_type_id' => 1, 'checklist_template_id' => 7, 'check_date' => '0000-00-00', 'period_key' => '2026-08-W2', 'status' => 'ng', 'checked_by' => 'Budi', 'created_at' => '2026-08-10 08:00:00']);

        $this->runImport();

        $log = ChecklistLog::firstOrFail();
        $this->assertSame('not_ok', $log->status);                  // ng → not_ok
        $this->assertSame('2026-08-01', $log->check_date->format('Y-m-d')); // weekly -W2 → 2026-08-01 (zero-date diganti)
    }
}
