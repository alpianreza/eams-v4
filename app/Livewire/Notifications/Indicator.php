<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Indicator extends Component
{
    public function render(): View
    {
        $unreadCount = auth()->check()
            ? Notification::query()->where('user_id', auth()->id())->whereNull('read_at')->count()
            : 0;

        return view('livewire.notifications.indicator', compact('unreadCount'));
    }
}
