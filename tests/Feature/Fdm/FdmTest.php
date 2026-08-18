<?php

namespace Tests\Feature\Fdm;

use App\Models\Fdm\FdmProductionSectionEntry;
use App\Models\Fdm\FdmProductionSectionYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FdmTest extends TestCase
{
    use RefreshDatabase;

    protected function writer(): User
    {
        return User::factory()->create(['role' => 'compliance', 'permission' => 'write']);
    }

    public function test_entry_is_saved_with_monthly_values(): void
    {
        $this->actingAs($this->writer())->post(route('fdm.entry.save'), [
            'report_year' => 2026, 'section_key' => 'produksi', 'section_label' => 'Produksi',
            'monthly_values' => [1 => 100, 2 => 200],
        ])->assertRedirect();

        $this->assertDatabaseHas('fdm_production_section_entries', ['section_key' => 'produksi']);
        $entry = FdmProductionSectionEntry::first();
        $this->assertSame(300.0, $entry->yearlyTotal());
    }

    public function test_entry_upserts_per_year_and_section(): void
    {
        $year = FdmProductionSectionYear::create(['report_year' => 2026]);
        $year->entries()->create(['section_key' => 'a', 'monthly_values' => [1 => 10]]);

        $this->actingAs($this->writer())->post(route('fdm.entry.save'), [
            'report_year' => 2026, 'section_key' => 'a', 'monthly_values' => [1 => 99],
        ]);

        $this->assertSame(1, FdmProductionSectionEntry::count());
        $this->assertSame(99.0, (float) FdmProductionSectionEntry::first()->monthly_values[1]);
    }
}
