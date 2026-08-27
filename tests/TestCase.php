<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Frontend production builds are verified separately in CI. Feature tests
        // can render Blade views without requiring a local Vite manifest.
        $this->withoutVite();
    }
}
