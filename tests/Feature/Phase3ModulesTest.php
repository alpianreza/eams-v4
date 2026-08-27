<?php

namespace Tests\Feature;

use App\Models\AssetItemType;
use App\Models\ChecklistMaster;
use App\Models\InventoryCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase3ModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'permission' => 'write',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_open_all_new_module_entry_pages(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('checklist-master.index'))->assertOk();
        $this->actingAs($admin)->get(route('settings.index'))->assertOk();
        $this->actingAs($admin)->get(route('print.index'))->assertOk();
    }

    public function test_non_admin_cannot_open_user_management(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'permission' => 'read']);

        $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
    }

    public function test_checklist_question_inherits_non_null_item_frequency(): void
    {
        $admin = $this->admin();
        $category = InventoryCategory::create(['name' => 'Fire Safety', 'code' => 'FS', 'active' => true]);
        $itemType = AssetItemType::create([
            'inventory_category_id' => $category->id,
            'name' => 'APAR',
            'code' => 'APAR',
            'checklist_frequency' => 'daily',
            'allow_na' => false,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('checklist-master.question.store', $itemType), ['question' => 'Tekanan normal?'])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_master', [
            'asset_item_type_id' => $itemType->id,
            'question' => 'Tekanan normal?',
            'frequency' => 'daily',
        ]);
        $this->assertSame('daily', ChecklistMaster::firstOrFail()->frequency);
    }

    public function test_sensitive_settings_are_encrypted_at_rest(): void
    {
        $admin = $this->admin();
        Setting::put('email_smtp_password', 'secret-value', true, $admin->id);

        $stored = DB::table('app_settings')->where('key', 'email_smtp_password')->value('value');
        $this->assertNotSame('secret-value', $stored);
        $this->assertSame('secret-value', Setting::value('email_smtp_password'));
    }
}
