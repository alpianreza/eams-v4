<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_application_boots_and_health_route_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_welcome_page_responds(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_eams_config_is_loaded(): void
    {
        $this->assertSame('2026-04-01', config('eams.saturday_holiday_effective'));
        $this->assertSame(600, config('eams.device_online_threshold_seconds'));
    }
}
