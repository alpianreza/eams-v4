<?php

namespace Tests\Feature\Import;

use App\Models\ComplianceInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    /** Point the READ-ONLY `legacy` connection at its own in-memory SQLite and build legacy-shaped tables. */
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

    public function test_import_users_carries_and_maps_idempotently(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, email TEXT, password TEXT, role TEXT, permission TEXT, status TEXT)',
        ]);
        $this->legacy()->table('users')->insert([
            'username' => 'asep', 'name' => 'Asep', 'email' => 'asep@x.id', 'password' => 'bcrypt-hash', 'role' => 'Compliance', 'permission' => 'write', 'status' => 'active',
        ]);

        $this->artisan('eams:import')->assertSuccessful();
        $this->artisan('eams:import')->assertSuccessful(); // re-run

        // idempotent: 1 user; role mapped to canonical; password carried
        $this->assertSame(1, User::where('username', 'asep')->count());
        $user = User::where('username', 'asep')->first();
        $this->assertSame('compliance', $user->role);
        $this->assertSame('bcrypt-hash', $user->password);
    }

    public function test_import_inventory_preserves_asset_code_and_maps_status(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, category_id INTEGER, item_type_id INTEGER, area_id INTEGER, asset_code TEXT, status TEXT, specific_area TEXT, qty INTEGER, active INTEGER)',
        ]);
        $this->legacy()->table('compliance_inventory')->insert([
            'asset_code' => 'APAR-001', 'status' => 'Need Repair', 'specific_area' => 'Lt. 1', 'qty' => 2, 'active' => 1,
        ]);

        $this->artisan('eams:import')->assertSuccessful();

        $inv = ComplianceInventory::where('asset_code', 'APAR-001')->firstOrFail();  // Q-020: exact
        $this->assertSame('need_repair', $inv->status);   // Q-017 transform
        $this->assertSame('Lt. 1', $inv->specific_area);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE areas (id INTEGER PRIMARY KEY, name TEXT, active INTEGER)',
        ]);
        $this->legacy()->table('areas')->insert(['name' => 'Gedung A', 'active' => 1]);

        $this->artisan('eams:import', ['--dry-run' => true])->assertSuccessful();

        // dry-run: nothing written
        $this->assertSame(0, \App\Models\Area::count());
    }

    public function test_missing_legacy_table_is_reported_not_fatal(): void
    {
        $this->setUpLegacy([]); // no legacy tables at all

        // import runs without crashing; missing tables are skipped/reported
        $this->artisan('eams:import')->assertSuccessful();
    }

    public function test_checklist_log_maps_checked_by_to_user_and_snapshot(): void
    {
        $this->setUpLegacy([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, name TEXT, email TEXT, password TEXT, role TEXT, permission TEXT, status TEXT)',
            'CREATE TABLE asset_item_types (id INTEGER PRIMARY KEY, code TEXT, name TEXT, checklist_frequency TEXT, allow_na INTEGER, active INTEGER)',
            'CREATE TABLE compliance_inventory (id INTEGER PRIMARY KEY, asset_item_type_id INTEGER, asset_code TEXT, status TEXT, active INTEGER)',
            'CREATE TABLE checklist_master (id INTEGER PRIMARY KEY, item_type_id INTEGER, question TEXT, frequency TEXT, require_photo INTEGER, active INTEGER)',
            'CREATE TABLE checklist_logs (id INTEGER PRIMARY KEY, inventory_id INTEGER, checklist_master_id INTEGER, period_key TEXT, time_slot TEXT, status TEXT, remark TEXT, photo TEXT, checked_by TEXT, mode TEXT)',
        ]);
        $this->legacy()->table('users')->insert(['username' => 'budi', 'name' => 'Budi', 'role' => 'compliance', 'permission' => 'write', 'status' => 'active']);
        $this->legacy()->table('asset_item_types')->insert(['id' => 1, 'code' => 'APAR', 'name' => 'APAR', 'checklist_frequency' => 'daily', 'allow_na' => 0, 'active' => 1]);
        $this->legacy()->table('compliance_inventory')->insert(['id' => 5, 'asset_item_type_id' => 1, 'asset_code' => 'APAR-001', 'status' => 'Good', 'active' => 1]);
        $this->legacy()->table('checklist_master')->insert(['id' => 7, 'item_type_id' => 1, 'question' => 'Tekanan?', 'frequency' => 'daily', 'require_photo' => 0, 'active' => 1]);
        $this->legacy()->table('checklist_logs')->insert(['inventory_id' => 5, 'checklist_master_id' => 7, 'period_key' => '2026-08-18', 'status' => 'ok', 'checked_by' => 'Budi']);

        $this->artisan('eams:import')->assertSuccessful();

        $log = \App\Models\ChecklistLog::firstOrFail();
        $this->assertSame('Budi', $log->checked_by_name);                 // Q-006 snapshot
        $this->assertNotNull($log->checked_by_user_id);                  // resolved to the user
        $this->assertSame('2026-08-18', $log->period_key);
    }
}
