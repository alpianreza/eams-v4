<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_shell_uses_tailwind_navigation_landmarks(): void
    {
        $user = User::factory()->create(['permission' => 'read']);

        $response = $this->actingAs($user)->get(route('home'))->assertOk();

        $response->assertSee('data-eams-shell="sidebar"', false)
            ->assertSee('data-eams-shell="topbar"', false)
            ->assertSee('data-eams-page-content', false)
            ->assertSee('x-data="eamsShell"', false)
            ->assertSee('wire:navigate', false)
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('eams:lg:w-20', false);
    }

    public function test_shell_dropdowns_no_longer_depend_on_bootstrap_javascript(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $themePicker = file_get_contents(resource_path('views/components/theme-picker.blade.php'));

        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $layout);
        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $themePicker);
        $this->assertStringContainsString('x-data="eamsDropdown"', $layout);
        $this->assertStringContainsString('x-data="eamsDropdown"', $themePicker);
    }
}
