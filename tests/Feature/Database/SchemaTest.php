<?php

namespace Tests\Feature\Database;

use App\Models\AssetItemType;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_tables_exist(): void
    {
        foreach ([
            'users', 'user_roles', 'areas', 'inventory_categories',
            'asset_item_types', 'holidays', 'employees', 'audit_logs', 'login_sessions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_asset_item_type_resolves_by_code_not_autoincrement_id(): void
    {
        $category = InventoryCategory::create(['name' => 'Fire Safety', 'code' => 'FS']);
        $type = AssetItemType::create([
            'inventory_category_id' => $category->id,
            'name' => 'APAR',
            'code' => 'APAR',
            'checklist_frequency' => 'monthly',
            'allow_na' => false,
        ]);

        // Q-015: behavior resolves by stable code, never by auto-increment id.
        $found = AssetItemType::findByCode('APAR');

        $this->assertNotNull($found);
        $this->assertSame($type->id, $found->id);
        $this->assertSame('monthly', $found->checklist_frequency);
        $this->assertFalse($found->allow_na);
    }

    public function test_user_authorization_helpers(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'permission' => 'write']);
        $reader = User::factory()->create(['role' => 'staff', 'permission' => 'read']);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasWriteAccess());
        $this->assertFalse($reader->hasWriteAccess());
        $this->assertTrue($admin->canAccessPage('anything')); // admin sees all (BR-44)
        $this->assertFalse($reader->canAccessPage('restricted')); // empty page_access
    }
}
