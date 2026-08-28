<?php

namespace App\Livewire\Dashboard;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Presentation boundary for the existing DashboardController KPI snapshot.
 *
 * Business calculations intentionally remain in DashboardController and
 * ChecklistPeriod. This component only renders and hydrates their result.
 */
class Overview extends Component
{
    #[Locked]
    public int $total = 0;

    /** @var array{good: int, need_repair: int, not_active: int} */
    #[Locked]
    public array $byStatus = [
        'good' => 0,
        'need_repair' => 0,
        'not_active' => 0,
    ];

    #[Locked]
    public int $open = 0;

    #[Locked]
    public int $late = 0;

    #[Locked]
    public int $expired = 0;

    /** @param array<string, int|numeric-string> $byStatus */
    public function mount(int $total, array $byStatus, int $open, int $late, int $expired): void
    {
        $this->total = $total;
        $this->byStatus = [
            'good' => (int) ($byStatus['good'] ?? 0),
            'need_repair' => (int) ($byStatus['need_repair'] ?? 0),
            'not_active' => (int) ($byStatus['not_active'] ?? 0),
        ];
        $this->open = $open;
        $this->late = $late;
        $this->expired = $expired;
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview');
    }
}
