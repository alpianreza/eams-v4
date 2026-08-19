<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UI polish: sidebar menu + branding + layout (BR-44 menu filtered by page_access). */
class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['permission' => 'read']);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('sidebar', false)          // sidebar present
            ->assertSee(config('eams.company_name')); // branding
    }

    public function test_admin_sees_admin_menu_group(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'permission' => 'write']);

        $this->actingAs($admin)->get(route('home'))->assertOk()->assertSee('Audit Logs');
    }

    public function test_non_admin_without_page_access_sees_fewer_items(): void
    {
        // staff with a limited page_access (array — the model array-casts it) — should NOT see admin-only items
        $staff = User::factory()->create(['role' => 'staff', 'permission' => 'read', 'page_access' => ['home']]);

        $response = $this->actingAs($staff)->get(route('home'))->assertOk();
        $response->assertDontSee('Audit Logs'); // admin-only hidden (BR-44)
    }
}
