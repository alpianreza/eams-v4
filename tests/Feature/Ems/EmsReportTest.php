<?php

namespace Tests\Feature\Ems;

use App\Models\Ems\EmsStationaryCombustionEntry;
use App\Models\Ems\EmsWaterConsumptionEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function writer(): User
    {
        return User::factory()->create(['role' => 'compliance', 'permission' => 'write']);
    }

    public function test_entry_is_saved_per_section_month_year(): void
    {
        $this->actingAs($this->writer())->post(route('ems.entry.save', 'water'), [
            'report_year' => 2026, 'section_key' => 'produksi-a', 'report_month' => 8, 'consumption_amount' => 1200.5,
        ])->assertRedirect();

        $this->assertDatabaseHas('ems_water_consumption_entries', ['report_year' => 2026, 'section_key' => 'produksi-a', 'report_month' => 8]);
    }

    public function test_entry_upserts_not_duplicates(): void
    {
        EmsWaterConsumptionEntry::create(['report_year' => 2026, 'section_key' => 'a', 'report_month' => 8, 'consumption_amount' => 100]);

        $this->actingAs($this->writer())->post(route('ems.entry.save', 'water'), [
            'report_year' => 2026, 'section_key' => 'a', 'report_month' => 8, 'consumption_amount' => 250,
        ]);

        // unique per section+month+year → updated, not duplicated
        $this->assertSame(1, EmsWaterConsumptionEntry::count());
        $this->assertSame(250.0, (float) EmsWaterConsumptionEntry::first()->consumption_amount);
    }

    public function test_matrix_groups_by_section_and_month(): void
    {
        EmsStationaryCombustionEntry::create(['report_year' => 2026, 'section_key' => 'boiler', 'report_month' => 1, 'consumption_amount' => 10]);
        EmsStationaryCombustionEntry::create(['report_year' => 2026, 'section_key' => 'boiler', 'report_month' => 2, 'consumption_amount' => 20]);

        $matrix = EmsStationaryCombustionEntry::matrixForYear(2026);

        $this->assertSame(10.0, $matrix['boiler'][1]);
        $this->assertSame(20.0, $matrix['boiler'][2]);
    }

    public function test_year_meta_can_be_saved(): void
    {
        $this->actingAs($this->writer())->post(route('ems.year.save', 'electric'), [
            'report_year' => 2026, 'production_output' => 50000, 'notes' => 'Target 2026',
        ])->assertRedirect();

        $this->assertDatabaseHas('ems_electric_consumption_years', ['report_year' => 2026, 'production_output' => 50000]);
    }

    public function test_unknown_category_is_404(): void
    {
        $this->actingAs($this->writer())->get(route('ems.index', 'nope'))->assertNotFound();
    }
}
