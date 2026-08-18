<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_username(): void
    {
        $user = User::factory()->create(['username' => 'budi', 'password' => bcrypt('secret')]);

        $response = $this->post('/login', ['login' => 'budi', 'password' => 'secret']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_authenticate_with_email(): void
    {
        $user = User::factory()->create(['email' => 'budi@eams.local', 'password' => bcrypt('secret')]);

        $this->post('/login', ['login' => 'budi@eams.local', 'password' => 'secret']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => bcrypt('secret')]);

        $this->post('/login', ['login' => 'budi', 'password' => 'wrong-password']);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => bcrypt('secret'), 'status' => 'inactive']);

        $this->post('/login', ['login' => 'budi', 'password' => 'secret']);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_successful_login_writes_an_audit_log(): void
    {
        $user = User::factory()->create(['username' => 'budi', 'password' => bcrypt('secret')]);

        $this->post('/login', ['login' => 'budi', 'password' => 'secret']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'status' => 'success',
        ]);
    }

    public function test_failed_login_is_audited_without_leaking_user(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => bcrypt('secret')]);

        $this->post('/login', ['login' => 'budi', 'password' => 'wrong']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'status' => 'failed',
        ]);
    }
}
