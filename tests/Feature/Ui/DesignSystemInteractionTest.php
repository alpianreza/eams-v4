<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemInteractionTest extends TestCase
{
    public function test_overlay_and_media_components_render_without_bootstrap_hooks(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.dropdown><x-slot:trigger><button>Menu</button></x-slot:trigger><span>Item</span></x-ui.dropdown>
            <x-ui.modal name="edit" title="Edit">Form</x-ui.modal>
            <x-ui.drawer name="filter" title="Filter">Filter body</x-ui.drawer>
            <x-ui.confirm-dialog name="delete" />
            <x-ui.file-upload name="photo" accept="image/*" />
            <x-ui.image-preview src="/photo.jpg" />
        BLADE);

        foreach (['dropdown', 'modal', 'drawer', 'confirm-dialog', 'file-upload', 'image-preview'] as $component) {
            $this->assertStringContainsString('data-eams-component="'.$component.'"', $html);
        }

        $this->assertStringNotContainsString('data-bs-toggle', $html);
        $this->assertStringContainsString('open-modal.window', $html);
        $this->assertStringContainsString('eams-confirmed', $html);
    }

    public function test_milestone_a_component_catalog_is_present(): void
    {
        $components = ['button', 'input', 'textarea', 'select', 'checkbox', 'radio', 'switch', 'badge', 'card', 'table', 'modal', 'drawer', 'toast', 'alert', 'dropdown', 'tabs', 'pagination', 'skeleton', 'empty-state', 'confirm-dialog', 'file-upload', 'image-preview', 'status-indicator'];

        foreach ($components as $component) {
            $this->assertFileExists(resource_path("views/components/ui/{$component}.blade.php"));
        }

        $this->assertFileExists(resource_path('views/components/breadcrumb.blade.php'));
    }
}
