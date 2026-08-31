<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemBlueprintComponentsTest extends TestCase
{
    public function test_page_header_renders_eyebrow_title_lead_and_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.page-header eyebrow="Compliance" eyebrow-icon="box-seam" title="Compliance Inventory"
                              lead="Pantau seluruh aset compliance." :back-url="route('home')">
                <x-slot:actions>
                    <x-ui.button variant="primary" icon="plus-lg">Tambah</x-ui.button>
                </x-slot:actions>
            </x-ui.page-header>
        BLADE);

        $this->assertStringContainsString('data-eams-component="page-header"', $html);
        $this->assertStringContainsString('Compliance Inventory', $html);
        $this->assertStringContainsString('Pantau seluruh aset compliance.', $html);
        $this->assertStringContainsString('wire:navigate', $html);
        $this->assertStringContainsString('Tambah', $html);
        $this->assertStringContainsString('bi-arrow-left', $html);
    }

    public function test_filter_bar_renders_search_and_reset_with_wire_hooks(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.filter-bar search="q" :has-filters="true" reset-action="resetFilters">
                <x-ui.select name="status" label="Status"><option value="good">Good</option></x-ui.select>
            </x-ui.filter-bar>
        BLADE);

        $this->assertStringContainsString('data-eams-component="filter-bar"', $html);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="q"', $html);
        $this->assertStringContainsString('wire:click="resetFilters"', $html);
        $this->assertStringNotContainsString('disabled>', $html);
    }

    public function test_filter_bar_disables_reset_when_no_filters_active(): void
    {
        $html = Blade::render('<x-ui.filter-bar search="q" :has-filters="false" />');

        $this->assertStringContainsString('disabled', $html);
    }

    public function test_stat_card_renders_formatted_value_with_icon_and_hint(): void
    {
        $html = Blade::render('<x-ui.stat-card label="Inventory aktif" :value="1234" icon="boxes" hint="Aset aktif monitoring." />');

        $this->assertStringContainsString('data-eams-component="card"', $html);
        $this->assertStringContainsString('Inventory aktif', $html);
        $this->assertStringContainsString('1,234', $html);
        $this->assertStringContainsString('bi-boxes', $html);
        $this->assertStringContainsString('Aset aktif monitoring.', $html);
    }

    public function test_period_chip_maps_canonical_period_statuses(): void
    {
        foreach (['DONE', 'OPEN', 'LATE', 'FUTURE', 'HOLIDAY'] as $status) {
            $html = Blade::render('<x-ui.period-chip status="'.$status.'" label="W1" />');

            $this->assertStringContainsString('data-status="'.$status.'"', $html, "Status $status harus tampil di chip");
            $this->assertStringContainsString('W1', $html);
        }
    }

    public function test_period_chip_renders_disabled_state_without_button(): void
    {
        $html = Blade::render('<x-ui.period-chip status="FUTURE" label="12" disabled title="Periode future terkunci" />');

        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringContainsString('Periode future terkunci', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    public function test_month_nav_renders_prev_next_with_carbon_label(): void
    {
        $html = Blade::render('<x-ui.month-nav :month="7" :year="2026" prev-url="/g?m=6" next-url="/g?m=8" />');

        $this->assertStringContainsString('data-eams-component="month-nav"', $html);
        $this->assertStringContainsString('Juli 2026', $html);
        $this->assertStringContainsString('href="/g?m=6"', $html);
        $this->assertStringContainsString('href="/g?m=8"', $html);
        $this->assertStringContainsString('Bulan sebelumnya', $html);
    }

    public function test_month_nav_locks_next_month_for_ranking_style_usage(): void
    {
        $html = Blade::render('<x-ui.month-nav :month="7" :year="2026" prev-url="/g?m=6" :disabled-next="true" />');

        $this->assertStringContainsString('Bulan depan terkunci', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    public function test_progress_renders_accessible_bar_with_auto_tone(): void
    {
        $full = Blade::render('<x-ui.progress :value="12" :max="12" label="Checklist hari ini" size="sm" />');
        $half = Blade::render('<x-ui.progress :value="6" :max="12" />');

        $this->assertStringContainsString('role="progressbar"', $full);
        $this->assertStringContainsString('aria-valuenow="100"', $full);
        $this->assertStringContainsString('12/12', $full);
        $this->assertStringContainsString('eams:bg-success', $full);
        $this->assertStringContainsString('aria-valuenow="50"', $half);
        $this->assertStringContainsString('eams:bg-info', $half);
    }

    public function test_timeline_renders_items_with_connector_and_empty_state(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.timeline :items="[
                ['title' => 'Scan masuk', 'meta' => '08:12', 'body' => 'Checkpoint A'],
                ['title' => 'Scan keluar', 'meta' => '08:30'],
            ]" dot-tone="success" />
        BLADE);

        $this->assertStringContainsString('data-eams-component="timeline"', $html);
        $this->assertStringContainsString('Scan masuk', $html);
        $this->assertStringContainsString('Checkpoint A', $html);

        $empty = Blade::render('<x-ui.timeline :items="[]" />');
        $this->assertStringContainsString('Tidak ada riwayat.', $empty);
    }

    public function test_repeater_renders_alpine_template_with_add_button(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.repeater name="measurements" label="Data pengukuran" add-label="Tambah pengukuran" :template="['value' => '']">
                <x-ui.input name="value" label="Nilai" />
            </x-ui.repeater>
        BLADE);

        $this->assertStringContainsString('data-eams-component="repeater"', $html);
        $this->assertStringContainsString('Tambah pengukuran', $html);
        $this->assertStringContainsString('x-for="(row, index) in rows"', $html);
        $this->assertStringContainsString('x-on:click="add()"', $html);
    }

    public function test_cascading_select_renders_level_selects_with_alpine_state(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.cascading-select source-url="/report/options"
                :levels="[
                    ['name' => 'category_id', 'label' => 'Kategori', 'placeholder' => 'Pilih kategori'],
                    ['name' => 'item_type_id', 'label' => 'Item', 'placeholder' => 'Pilih item'],
                    ['name' => 'inventory_id', 'label' => 'Inventory', 'placeholder' => 'Pilih inventory'],
                ]" />
        BLADE);

        $this->assertStringContainsString('data-eams-component="cascading-select"', $html);
        $this->assertStringContainsString('Kategori', $html);
        $this->assertStringContainsString('Item', $html);
        $this->assertStringContainsString('Inventory', $html);
        $this->assertStringContainsString('x-model="values[level.name]"', $html);
    }

    public function test_period_strip_renders_month_nav_and_chips_with_navigation_and_locked_states(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.period-strip :month="8" :year="2026" frequency="Weekly"
                prev-url="/p?m=7" next-url="/p?m=9" current-key="2026-08-W2"
                :periods="[
                    ['key' => '2026-08-W1', 'label' => 'W1', 'status' => 'DONE', 'editable' => false, 'reason' => 'Selesai'],
                    ['key' => '2026-08-W2', 'label' => 'W2', 'status' => 'OPEN', 'editable' => true, 'url' => '/fill?p=W2'],
                    ['key' => '2026-08-W3', 'label' => 'W3', 'status' => 'FUTURE', 'editable' => false, 'reason' => 'Belum dibuka'],
                ]" />
        BLADE);

        $this->assertStringContainsString('data-eams-component="period-strip"', $html);
        $this->assertStringContainsString('Weekly', $html);
        $this->assertStringContainsString('Agustus 2026', $html);
        $this->assertStringContainsString('href="/fill?p=W2"', $html);
        $this->assertStringContainsString('wire:navigate', $html);
        $this->assertStringContainsString('title="Selesai"', $html);
        $this->assertStringContainsString('title="Belum dibuka"', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    public function test_data_grid_renders_headers_sticky_columns_and_empty_state(): void
    {
        $grid = Blade::render(<<<'BLADE'
            <x-ui.data-grid :headers="[
                ['label' => 'Aset', 'scope' => 'col'],
                ['label' => 'Senin 24', 'scope' => 'col'],
                ['label' => 'Sabtu 29 (Libur)', 'scope' => 'col', 'offday' => true],
            ]" label="Matriks Test">
                <tr>
                    <td>Aset 1</td>
                    <td data-grid-cell tabindex="0">OK</td>
                    <td data-grid-cell data-locked tabindex="-1">Libur</td>
                </tr>
            </x-ui.data-grid>
        BLADE);

        $this->assertStringContainsString('data-eams-component="data-grid"', $html = $grid);
        $this->assertStringContainsString('aria-label="Matriks Test"', $html);
        $this->assertStringContainsString('Aset', $html);
        $this->assertStringContainsString('data-offday', $html);
        $this->assertStringContainsString('eams:sticky', $html);
        $this->assertStringContainsString('data-grid-cell', $html);

        $empty = Blade::render('<x-ui.data-grid :headers="[\'Col 1\', \'Col 2\']" empty-text="Tidak ada inventaris." />');
        $this->assertStringContainsString('Tidak ada inventaris.', $empty);
    }

    public function test_new_components_never_emit_bootstrap_hooks_or_svg(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.page-header title="T" eyebrow="E" />
            <x-ui.filter-bar search="q" />
            <x-ui.stat-card label="L" :value="1" />
            <x-ui.period-chip status="OPEN" label="P" />
            <x-ui.month-nav :month="1" :year="2026" prev-url="/p" next-url="/n" />
            <x-ui.period-strip :month="1" :year="2026" :periods="[['key' => '1', 'status' => 'OPEN']]" />
            <x-ui.data-grid :headers="['A']" />
            <x-ui.progress :value="3" :max="10" />
            <x-ui.timeline :items="[['title' => 'A']]" />
            <x-ui.repeater name="r" />
            <x-ui.cascading-select source-url="/x" :levels="[['name' => 'a', 'label' => 'A']]" />
        BLADE);

        $this->assertStringNotContainsString('data-bs-toggle', $html);
        $this->assertStringNotContainsString('data-bs-dismiss', $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringNotContainsString('class="btn ', $html);
    }
}
