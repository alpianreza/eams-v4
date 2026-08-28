<?php

namespace Tests\Feature\Ui;

use App\Livewire\Dashboard\Overview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_livewire_component_renders_controller_snapshot_with_canonical_presentations(): void
    {
        Livewire::test(Overview::class, [
            'total' => 7,
            'byStatus' => ['good' => 4, 'need_repair' => 2, 'not_active' => 1],
            'open' => 3,
            'late' => 2,
            'expired' => 1,
        ])
            ->assertStatus(200)
            ->assertSet('total', 7)
            ->assertSet('byStatus', ['good' => 4, 'need_repair' => 2, 'not_active' => 1])
            ->assertSet('open', 3)
            ->assertSet('late', 2)
            ->assertSet('expired', 1)
            ->assertSeeHtml('data-eams-livewire="dashboard-overview"')
            ->assertSeeHtml('data-dashboard-kpi="total"')
            ->assertSeeHtml('data-dashboard-kpi="open"')
            ->assertSeeHtml('data-dashboard-kpi="late"')
            ->assertSeeHtml('data-dashboard-kpi="expired"')
            ->assertSeeHtml('data-status="GOOD"')
            ->assertSeeHtml('data-status="NEED_REPAIR"')
            ->assertSeeHtml('data-status="NOT_ACTIVE"')
            ->assertSeeHtml('wire:navigate')
            ->assertSee('Inventory aktif')
            ->assertSee('Checklist open')
            ->assertSee('Checklist late')
            ->assertSee('Expired (mis. APAR)')
            ->assertSee('Penjelasan status')
            ->assertDontSee('data-bs-toggle', false)
            ->assertDontSee('row g-3', false);
    }

    public function test_dashboard_route_keeps_existing_contract_and_renders_livewire_surface(): void
    {
        $user = User::factory()->create(['permission' => 'write']);

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('data-eams-livewire="dashboard-overview"', false)
            ->assertSee('wire:id=', false)
            ->assertSee('Inventory aktif')
            ->assertSee('Checklist open')
            ->assertSee('Checklist late')
            ->assertSee('Expired (mis. APAR)')
            ->assertSee('data-dashboard-link="inventory"', false)
            ->assertDontSee('data-bs-toggle', false)
            ->assertDontSee('row g-3', false)
            ->assertDontSee('col-md-3', false);
    }
}
