<?php

namespace Tests\Feature\Patrol;

use App\Models\Patrol\PatrolCheckpoint;
use App\Models\Patrol\PatrolLog;
use App\Models\Patrol\PatrolRoute;
use App\Models\Patrol\PatrolSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatrolTest extends TestCase
{
    use RefreshDatabase;

    protected function guard(): User
    {
        return User::factory()->create(['role' => 'security', 'permission' => 'write']);
    }

    protected function makeRouteWithCheckpoints(): PatrolRoute
    {
        $route = PatrolRoute::create(['name' => 'Rute 1-2', 'slug' => 'forward', 'active' => true]);
        $cp1 = PatrolCheckpoint::create(['code' => 'CP1', 'name' => 'Pos Bambu', 'barcode_value' => 'PATROL-CP1', 'active' => true]);
        $cp2 = PatrolCheckpoint::create(['code' => 'CP2', 'name' => 'Area B3', 'barcode_value' => 'PATROL-CP2', 'active' => true]);
        $route->checkpoints()->attach($cp1->id, ['route_order' => 1]);
        $route->checkpoints()->attach($cp2->id, ['route_order' => 2]);

        return $route;
    }

    public function test_start_session_sets_total_checkpoints(): void
    {
        $route = $this->makeRouteWithCheckpoints();

        $this->actingAs($this->guard())->post(route('patrol.start'), ['patrol_route_id' => $route->id])->assertRedirect();

        $this->assertDatabaseHas('patrol_sessions', ['patrol_route_id' => $route->id, 'status' => 'active', 'total_checkpoints' => 2]);
    }

    public function test_scan_records_log_and_completes_when_all_checked(): void
    {
        $route = $this->makeRouteWithCheckpoints();
        $guard = $this->guard();
        $session = PatrolSession::create([
            'patrol_route_id' => $route->id, 'patrol_date' => now()->toDateString(),
            'started_by' => $guard->id, 'status' => 'active', 'total_checkpoints' => 2,
        ]);

        $this->actingAs($guard)->post(route('patrol.scan', $session), ['barcode_value' => 'PATROL-CP1', 'status' => 'ok'])->assertRedirect();
        $this->actingAs($guard)->post(route('patrol.scan', $session), ['barcode_value' => 'PATROL-CP2', 'status' => 'issue', 'note' => 'Pintu rusak'])->assertRedirect();

        $this->assertSame(2, PatrolLog::where('patrol_session_id', $session->id)->count());
        $fresh = $session->fresh();
        $this->assertSame(2, $fresh->checked_count);
        $this->assertSame(1, $fresh->issue_count);
        $this->assertSame('completed', $fresh->status);  // auto-complete when all checked
        $this->assertNotNull($fresh->ended_at);
    }

    public function test_scan_rejects_unknown_barcode(): void
    {
        $route = $this->makeRouteWithCheckpoints();
        $guard = $this->guard();
        $session = PatrolSession::create([
            'patrol_route_id' => $route->id, 'patrol_date' => now()->toDateString(),
            'started_by' => $guard->id, 'status' => 'active', 'total_checkpoints' => 2,
        ]);

        $this->actingAs($guard)->post(route('patrol.scan', $session), ['barcode_value' => 'UNKNOWN', 'status' => 'ok'])
            ->assertSessionHasErrors('barcode_value');
    }

    public function test_scan_rejects_checkpoint_outside_route(): void
    {
        $route = $this->makeRouteWithCheckpoints();
        PatrolCheckpoint::create(['code' => 'CPX', 'name' => 'Other', 'barcode_value' => 'PATROL-CPX', 'active' => true]);
        $guard = $this->guard();
        $session = PatrolSession::create([
            'patrol_route_id' => $route->id, 'patrol_date' => now()->toDateString(),
            'started_by' => $guard->id, 'status' => 'active', 'total_checkpoints' => 2,
        ]);

        $this->actingAs($guard)->post(route('patrol.scan', $session), ['barcode_value' => 'PATROL-CPX', 'status' => 'ok'])
            ->assertSessionHasErrors('barcode_value');
    }

    public function test_scan_rejects_double_scan_in_same_session(): void
    {
        $route = $this->makeRouteWithCheckpoints();
        $guard = $this->guard();
        $session = PatrolSession::create([
            'patrol_route_id' => $route->id, 'patrol_date' => now()->toDateString(),
            'started_by' => $guard->id, 'status' => 'active', 'total_checkpoints' => 2,
        ]);

        $this->actingAs($guard)->post(route('patrol.scan', $session), ['barcode_value' => 'PATROL-CP1', 'status' => 'ok']);
        $this->actingAs($guard)->post(route('patrol.scan', $session), ['barcode_value' => 'PATROL-CP1', 'status' => 'ok'])
            ->assertSessionHasErrors('barcode_value');
    }

    public function test_checkpoint_gps_distance_is_computed(): void
    {
        $cp = PatrolCheckpoint::create(['code' => 'CP1', 'name' => 'Pos', 'barcode_value' => 'X', 'lat' => -6.906502, 'lng' => 106.778173, 'radius_m' => 10]);

        $near = $cp->distanceFrom(-6.906502, 106.778173);       // same point
        $far = $cp->distanceFrom(-6.920000, 106.790000);        // ~2km away

        $this->assertSame(0.0, $near);
        $this->assertGreaterThan(10, $far);
    }
}
