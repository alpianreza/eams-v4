<?php

namespace Tests\Feature\Thermal;

use App\Models\Thermal\ThermalImagingLocation;
use App\Models\Thermal\ThermalImagingReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThermalImagingTest extends TestCase
{
    use RefreshDatabase;

    protected function writer(): User
    {
        return User::factory()->create(['role' => 'compliance', 'permission' => 'write']);
    }

    public function test_report_is_created(): void
    {
        $this->actingAs($this->writer())->post(route('thermal.store'), [
            'inspection_date' => '2026-08-18', 'inspector_name' => 'Asep', 'facility' => 'Gedung A',
        ])->assertRedirect();

        $this->assertDatabaseHas('thermal_imaging_reports', ['inspection_date' => '2026-08-18', 'inspector_name' => 'Asep']);
    }

    public function test_report_item_is_added_with_location_name_resolved(): void
    {
        $report = ThermalImagingReport::create(['inspection_date' => '2026-08-18']);
        $location = ThermalImagingLocation::create(['name' => 'Panel MDP-1', 'active' => true]);

        $this->actingAs($this->writer())->post(route('thermal.items.store', $report), [
            'location_id' => $location->id, 'celsius' => 68.5, 'findings' => 'Panas berlebih',
        ])->assertRedirect();

        // location_name snapshot resolved from the location master
        $this->assertDatabaseHas('thermal_imaging_report_items', ['report_id' => $report->id, 'location_name' => 'Panel MDP-1', 'celsius' => 68.5]);
    }

    public function test_report_shows_items(): void
    {
        $report = ThermalImagingReport::create(['inspection_date' => '2026-08-18']);
        $report->items()->create(['location_name' => 'Panel MDP-1', 'celsius' => 68.5, 'sort_order' => 1]);

        $this->actingAs($this->writer())->get(route('thermal.show', $report))->assertOk()->assertSee('Panel MDP-1');
    }
}
