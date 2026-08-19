<?php

namespace App\Http\Controllers;

use App\Services\Checklist\WeeklyChecklistReminder;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Home (§7): personal pending checklist tasks (via PIC relation) + unread notifications. */
class HomeController extends Controller
{
    public function __invoke(Request $request, WeeklyChecklistReminder $reminder): View
    {
        $user = $request->user();

        return view('home', [
            'pending' => $reminder->pendingForUser($user),
            'unreadNotifications' => NotificationService::unreadCount($user),
        ]);
    }
}
