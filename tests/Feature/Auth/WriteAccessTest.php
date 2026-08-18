<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Write-guard (BR-42) + role gate for master data management.
 */
class WriteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_user_is_blocked_from_mutations(): void
    {
        // compliance role, but read-only permission → global write-guard blocks (403).
        $reader = User::factory()->create(['role' => 'compliance', 'permission' => 'read']);

        $this->actingAs($reader)->post(route('master-data.areas.store'), ['name' => 'X'])
            ->assertForbidden();

        $this->assertDatabaseMissing('areas', ['name' => 'X']);
    }

    public function test_write_user_without_master_data_role_is_blocked_by_gate(): void
    {
        // staff with write permission passes the write-guard, but lacks the master-data role → gate blocks (403).
        $staff = User::factory()->create(['role' => 'staff', 'permission' => 'write']);

        $this->actingAs($staff)->post(route('master-data.areas.store'), ['name' => 'X'])
            ->assertForbidden();
    }

    public function test_admin_with_write_access_can_mutate(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'permission' => 'write']);

        $this->actingAs($admin)->post(route('master-data.areas.store'), ['name' => 'Gedung A'])
            ->assertRedirect();

        $this->assertDatabaseHas('areas', ['name' => 'Gedung A']);
    }

    public function test_read_only_user_can_still_view_and_logout(): void
    {
        $reader = User::factory()->create(['role' => 'staff', 'permission' => 'read']);

        $this->actingAs($reader)->get(route('master-data.areas.index'))->assertOk();
        $this->actingAs($reader)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
