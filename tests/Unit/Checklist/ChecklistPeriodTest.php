<?php

namespace Tests\Unit\Checklist;

use App\Models\Holiday;
use App\Support\Checklist\ChecklistPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChecklistPeriodTest extends TestCase
{
    use RefreshDatabase; // holidays table is consulted by isOffday()

    /* ---- period_key formats (BR-01) ---- */

    public function test_daily_period_key(): void
    {
        $this->assertSame('2026-08-18', ChecklistPeriod::periodKey('daily', Carbon::parse('2026-08-18')));
    }

    public function test_monthly_period_key(): void
    {
        $this->assertSame('2026-08', ChecklistPeriod::periodKey('monthly', Carbon::parse('2026-08-18')));
    }

    /* ---- weekly month-slice (BR-02, NOT ISO week) ---- */

    public function test_weekly_uses_month_slices_not_iso_week(): void
    {
        $cases = [1 => 'W1', 7 => 'W1', 8 => 'W2', 14 => 'W2', 15 => 'W3', 21 => 'W3', 22 => 'W4', 31 => 'W4'];
        foreach ($cases as $day => $week) {
            $date = Carbon::parse("2026-08-".str_pad((string) $day, 2, '0', STR_PAD_LEFT));
            $this->assertSame("2026-08-{$week}", ChecklistPeriod::periodKey('weekly', $date), "day {$day}");
        }
    }

    /* ---- offday: Sunday always off ---- */

    public function test_sunday_is_always_offday(): void
    {
        $sunday = Carbon::parse('2026-08-16'); // a Sunday
        $this->assertTrue($sunday->isSunday());
        $this->assertTrue(ChecklistPeriod::isOffday($sunday));
    }

    /* ---- Saturday effective date (Q-005), NOT retroactive ---- */

    public function test_saturday_before_effective_date_is_a_working_day(): void
    {
        $saturdayBefore = Carbon::parse('2026-03-25')->next(Carbon::SATURDAY); // Saturday before 2026-04-01
        $this->assertTrue($saturdayBefore->isSaturday());
        $this->assertFalse(ChecklistPeriod::isOffday($saturdayBefore));
    }

    public function test_saturday_from_effective_date_is_a_holiday(): void
    {
        $saturdayAfter = Carbon::parse('2026-04-01')->next(Carbon::SATURDAY); // Saturday on/after 2026-04-01
        $this->assertTrue($saturdayAfter->isSaturday());
        $this->assertTrue(ChecklistPeriod::isOffday($saturdayAfter));
    }

    public function test_holiday_table_date_is_an_offday(): void
    {
        Holiday::create(['holiday_date' => '2026-08-17', 'description' => 'HUT RI']);
        $this->assertTrue(ChecklistPeriod::isOffday(Carbon::parse('2026-08-17')));
        $this->assertFalse(ChecklistPeriod::isOffday(Carbon::parse('2026-08-18')));
    }

    /* ---- status: DONE / OPEN / LATE / FUTURE / HOLIDAY (Q-004) ---- */

    public function test_future_period(): void
    {
        $now = Carbon::parse('2026-08-18 09:00');
        $this->assertSame('FUTURE', ChecklistPeriod::status('daily', Carbon::parse('2026-08-19'), false, $now));
    }

    public function test_done_period(): void
    {
        $now = Carbon::parse('2026-08-18 09:00');
        $this->assertSame('DONE', ChecklistPeriod::status('daily', Carbon::parse('2026-08-18'), true, $now));
    }

    public function test_open_period(): void
    {
        $now = Carbon::parse('2026-08-18 09:00');
        $this->assertSame('OPEN', ChecklistPeriod::status('daily', Carbon::parse('2026-08-18'), false, $now));
    }

    public function test_late_period_is_time_based(): void
    {
        // daily late after +21 days (BR-03)
        $period = Carbon::parse('2026-07-20');
        $now = Carbon::parse('2026-08-18'); // > 2026-07-20 end + 21d
        $this->assertSame('LATE', ChecklistPeriod::status('daily', $period, false, $now));
    }

    public function test_daily_on_offday_is_holiday_not_open(): void
    {
        $sunday = Carbon::parse('2026-08-16');
        $now = Carbon::parse('2026-08-16 09:00');
        $this->assertSame('HOLIDAY', ChecklistPeriod::status('daily', $sunday, false, $now));
    }

    /* ---- editable (BR-04 / BR-08) ---- */

    public function test_daily_entry_blocked_on_offday(): void
    {
        $sunday = Carbon::parse('2026-08-16');
        $this->assertFalse(ChecklistPeriod::isEditable('daily', $sunday, Carbon::parse('2026-08-16 09:00')));
    }

    public function test_future_period_not_editable(): void
    {
        $this->assertFalse(ChecklistPeriod::isEditable('daily', Carbon::parse('2026-08-19'), Carbon::parse('2026-08-18 09:00')));
    }

    public function test_current_working_day_is_editable(): void
    {
        $this->assertTrue(ChecklistPeriod::isEditable('daily', Carbon::parse('2026-08-18'), Carbon::parse('2026-08-18 09:00')));
    }
}
