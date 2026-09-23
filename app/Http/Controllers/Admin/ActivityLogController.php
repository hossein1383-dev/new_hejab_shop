<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $logs = ActivityLog::with('user')->latest()->paginate(30);

        return view('admin.activity-logs.index', compact('logs'));
    }
}
