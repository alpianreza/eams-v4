<?php

namespace Tests\Feature\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function writer(): User
    {
        return User::factory()->create(['role' => 'compliance', 'permission' => 'write']);
    }

    public function test_user_can_create_calendar_event(): void
    {
        $this->actingAs($this->writer())->post(route('calendar.store'), [
            'title' => 'Audit ISO', 'start_at' => '2026-08-20', 'color' => '#0d6efd',
        ])->assertRedirect();

        $this->assertDatabaseHas('compliance_calendar_events', ['title' => 'Audit ISO']);
    }

    public function test_calendar_shows_event_and_holiday(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        CalendarEvent::create(['title' => 'Audit ISO', 'start_at' => '2026-08-20', 'created_by' => null]);
        Holiday::create(['holiday_date' => '2026-08-17', 'description' => 'HUT RI']);

        $this->actingAs($this->writer())->get(route('calendar.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Audit ISO')
            ->assertSee('HUT RI');
    }

    public function test_calendar_marks_saturday_and_sunday_as_offday(): void
    {
        // 2026-08-15 is a Saturday (after the 2026-04-01 effective date), 2026-08-16 a Sunday.
        $this->actingAs($this->writer())->get(route('calendar.index', ['month' => '2026-08']))
            ->assertOk()->assertSee('table-secondary', false); // offday cells shaded
    }

    public function test_read_only_user_cannot_create_event(): void
    {
        $reader = User::factory()->create(['role' => 'compliance', 'permission' => 'read']);

        $this->actingAs($reader)->post(route('calendar.store'), ['title' => 'X', 'start_at' => '2026-08-20'])
            ->assertForbidden();
    }
}
