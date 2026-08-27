<?php

namespace Tests\Feature\Ui;

use Composer\InstalledVersions;
use Tests\TestCase;

class FrontendFoundationTest extends TestCase
{
    public function test_livewire_four_is_locked_and_available(): void
    {
        $version = InstalledVersions::getPrettyVersion('livewire/livewire');

        $this->assertNotNull($version);
        $this->assertMatchesRegularExpression('/^v?4\./', $version);
        $this->assertTrue(class_exists(\Livewire\Livewire::class));
    }

    public function test_frontend_uses_livewire_alpine_runtime_once(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('vendor/livewire/livewire/dist/livewire.esm', $javascript);
        $this->assertStringContainsString('Livewire.start()', $javascript);
        $this->assertStringNotContainsString("from 'alpinejs'", $javascript);
        $this->assertDoesNotMatchRegularExpression('/^\s*Alpine\.start\(\);/m', $javascript);
    }

    public function test_tailwind_is_prefixed_and_does_not_load_preflight(): void
    {
        $stylesheet = file_get_contents(resource_path('css/tailwind.css'));

        $this->assertStringContainsString('prefix(eams)', $stylesheet);
        $this->assertStringContainsString("tailwindcss/utilities.css", $stylesheet);
        $this->assertStringNotContainsString('preflight.css', $stylesheet);
    }
}
