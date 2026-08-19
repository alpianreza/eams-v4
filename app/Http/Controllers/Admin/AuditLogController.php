<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin: audit log viewer (append-only trail). */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->latest('id')
            ->paginate(30);

        return view('admin.audit-logs.index', ['logs' => $logs]);
    }
}
