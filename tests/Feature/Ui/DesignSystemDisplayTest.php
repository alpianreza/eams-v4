<?php

namespace Tests\Feature\Ui;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemDisplayTest extends TestCase
{
    public function test_data_and_feedback_components_render(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.card title="Inventory"><p>Body</p></x-ui.card>
            <x-ui.table label="Daftar"><thead><tr><th>Asset</th></tr></thead><tbody><tr><td>A-01</td></tr></tbody></x-ui.table>
            <x-ui.tabs :tabs="[['id' => 'detail', 'label' => 'Detail']]" active="detail"><div x-show="active === 'detail'">Panel</div></x-ui.tabs>
            <x-ui.skeleton :lines="2" />
            <x-ui.empty-state title="Kosong" description="Belum ada item" />
            <x-ui.toast variant="success">Tersimpan</x-ui.toast>
        BLADE);

        foreach (['card', 'table', 'tabs', 'skeleton', 'empty-state', 'toast'] as $component) {
            $this->assertStringContainsString('data-eams-component="'.$component.'"', $html);
        }
    }

    public function test_compact_pagination_uses_fixed_icons_and_wire_navigation(): void
    {
        $paginator = new LengthAwarePaginator(
            items: range(11, 20),
            total: 50,
            perPage: 10,
            currentPage: 2,
            options: ['path' => '/inventory'],
        );

        $html = Blade::render('<x-ui.pagination :paginator="$paginator" />', compact('paginator'));

        $this->assertStringContainsString('data-eams-component="pagination"', $html);
        $this->assertStringContainsString('wire:navigate', $html);
        $this->assertStringContainsString('bi-chevron-right', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }
}
