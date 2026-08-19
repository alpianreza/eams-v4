<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Admin: login session viewer + force-end. */
class LoginSessionController extends Controller
{
    public function index(): View
    {
        $sessions = LoginSession::with('user')->latest('last_seen_at')->paginate(30);

        return view('admin.login-sessions.index', ['sessions' => $sessions]);
    }

    /** Force-end a session (admin). */
    public function end(LoginSession $session): RedirectResponse
    {
        $session->update(['is_active' => false, 'ended_at' => now(), 'logout_reason' => 'force_end_by_admin']);

        return back()->with('status', 'Sesi diakhiri paksa.');
    }
}
