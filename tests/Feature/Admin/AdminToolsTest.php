<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\LoginSession;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'permission' => 'write']);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = $this->admin();
        AuditLog::create(['user_id' => $admin->id, 'action' => 'login', 'description' => 'User login', 'ip_address' => '127.0.0.1']);

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk()->assertSee('User login');
    }

    public function test_non_admin_cannot_access_admin_tools(): void
    {
        $compliance = User::factory()->create(['role' => 'compliance', 'permission' => 'write']);

        $this->actingAs($compliance)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($compliance)->get(route('admin.backups.index'))->assertForbidden();
    }

    public function test_admin_can_view_and_force_end_login_session(): void
    {
        $admin = $this->admin();
        $session = LoginSession::create([
            'session_key' => 'k1', 'user_id' => $admin->id, 'username' => 'admin',
            'started_at' => now(), 'last_seen_at' => now(), 'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.login-sessions.index'))->assertOk()->assertSee('admin');
        $this->actingAs($admin)->post(route('admin.login-sessions.end', $session))->assertRedirect();

        $this->assertFalse((bool) $session->fresh()->is_active);
    }

    public function test_admin_can_trigger_backup_via_ui(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())->post(route('admin.backups.store'))->assertRedirect();

        $this->assertDatabaseHas('backups', ['type' => 'database', 'status' => 'done']);
        Storage::disk('local')->assertExists(Backup::first()->path);
    }
}
