<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminCustomField;
use App\Models\AdminLog;
use App\Models\AdminModule;
use App\Models\AdminRole;
use App\Models\TaxDueDate;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users'          => User::count(),
            'modules_active' => AdminModule::where('active', true)->count(),
            'modules_total'  => AdminModule::count(),
            'fields_total'   => AdminCustomField::count(),
            'roles_total'    => AdminRole::count(),
            'tax_due_dates'  => TaxDueDate::where('year', date('Y'))->count(),
        ];

        $recentLogs = AdminLog::orderByDesc('created_at')->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recentLogs'));
    }
}
