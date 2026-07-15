<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminLog::orderByDesc('created_at');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs    = $query->paginate(50)->withQueryString();
        $modules = AdminLog::select('module')->distinct()->whereNotNull('module')->pluck('module');
        $actions = AdminLog::select('action')->distinct()->pluck('action');

        return view('admin.logs.index', compact('logs', 'modules', 'actions'));
    }
}
