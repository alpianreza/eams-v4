<?php

namespace Tests\Feature\Ui;

use App\Livewire\Notifications\Indicator;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_indicator_renders_and_is_scoped_to_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Notification::create(['user_id' => $user->id, 'type' => 'info', 'title' => 'Unread']);
        Notification::create(['user_id' => $other->id, 'type' => 'info', 'title' => 'Other']);

        Livewire::actingAs($user)
            ->test(Indicator::class)
            ->assertStatus(200)
            ->assertSeeHtml('data-eams-livewire="notification-indicator"')
            ->assertSee('1')
            ->assertSeeHtml('wire:poll.60s')
            ->assertSeeHtml('wire:navigate');
    }
}
