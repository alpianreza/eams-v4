<?php

namespace Tests\Unit;

use Tests\TestCase;

class EamsConfigTest extends TestCase
{
    public function test_saturday_holiday_effective_date_is_configurable_and_not_retroactive(): void
    {
        // Decision Q-005: Saturday becomes a holiday from 2026-04-01 onward.
        $this->assertSame('2026-04-01', config('eams.saturday_holiday_effective'));
    }

    public function test_device_online_threshold_is_centralized(): void
    {
        // Decision Q-012: single centralized threshold; device ONLINE when last_seen <= 600s.
        $this->assertSame(600, config('eams.device_online_threshold_seconds'));
    }

    public function test_storage_categories_are_defined(): void
    {
        // Decision Q-022: logical storage categories.
        $this->assertSame(
            ['inventory', 'checklist', 'qr', 'attachments'],
            config('eams.storage_categories')
        );
    }

    public function test_filesystem_defines_a_disk_per_eams_category(): void
    {
        // Decision Q-022: a configurable disk exists per business-file category.
        foreach (['inventory', 'checklist', 'qr', 'attachments'] as $disk) {
            $this->assertArrayHasKey($disk, config('filesystems.disks'));
        }
    }
}
