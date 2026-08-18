<?php

namespace Tests\Feature\Utility;

use App\Models\User;
use App\Models\Utility\BoilerFuelLog;
use App\Models\Utility\PdamWaterBoilerLog;
use App\Models\Utility\PdamWaterLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UtilityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function writer(): User
    {
        return User::factory()->create(['role' => 'staff', 'permission' => 'write']);
    }

    public function test_daily_log_is_created(): void
    {
        $this->actingAs($this->writer())->post(route('utility.store', 'boiler'), [
            'log_date' => '2026-08-18', 'log_time' => '08:00', 'kg' => 120.5, 'polybag' => 3, 'note' => 'Pagi',
        ])->assertRedirect();

        $this->assertDatabaseHas('boiler_fuel_logs', ['log_date' => '2026-08-18', 'kg' => 120.5]);
    }

    public function test_pdam_water_boiler_is_one_per_day(): void
    {
        PdamWaterBoilerLog::create(['log_date' => '2026-08-18', 'meter_reading' => 100]);

        // legacy: 1 data per day (unique log_date)
        $this->actingAs($this->writer())->post(route('utility.store', 'pdam-water-boiler'), [
            'log_date' => '2026-08-18', 'meter_reading' => 200,
        ])->assertSessionHasErrors('log_date');

        $this->assertSame(1, PdamWaterBoilerLog::count());
    }

    public function test_meter_style_monthly_total_is_last_minus_first(): void
    {
        PdamWaterLog::create(['log_date' => '2026-08-01', 'meter_reading' => 1000]);
        PdamWaterLog::create(['log_date' => '2026-08-31', 'meter_reading' => 1150]);

        // consumption = last - first reading (not sum)
        $this->assertSame(150.0, PdamWaterLog::monthlyTotal('2026-08'));
    }

    public function test_consumable_monthly_total_is_sum(): void
    {
        BoilerFuelLog::create(['log_date' => '2026-08-01', 'kg' => 100]);
        BoilerFuelLog::create(['log_date' => '2026-08-02', 'kg' => 50]);

        $this->assertSame(150.0, BoilerFuelLog::monthlyTotal('2026-08'));
    }

    public function test_index_marks_offdays(): void
    {
        Carbon::setTestNow('2026-08-18 09:00:00');
        $this->actingAs($this->writer())->get(route('utility.index', ['type' => 'boiler', 'month' => '2026-08']))
            ->assertOk()->assertSee('libur'); // offday badge rendered (Sundays/holidays)
    }

    public function test_read_only_user_cannot_write_utility_log(): void
    {
        $reader = User::factory()->create(['role' => 'staff', 'permission' => 'read']);

        $this->actingAs($reader)->post(route('utility.store', 'boiler'), ['log_date' => '2026-08-18', 'kg' => 1])
            ->assertForbidden(); // global write-guard
    }
}
