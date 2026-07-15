<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\AdminModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = AdminModule::orderBy('order')->get();

        return view('admin.modules.index', compact('modules'));
    }

    public function toggle(AdminModule $module): RedirectResponse
    {
        $old = ['active' => $module->active];
        $module->update(['active' => ! $module->active]);

        AdminLog::record('toggled', 'modules', $module->id, $old, ['active' => $module->active]);

        $estado = $module->active ? 'activado' : 'desactivado';

        return back()->with('success', "Módulo \"{$module->name}\" {$estado}.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->order as $position => $id) {
            AdminModule::where('id', $id)->update(['order' => $position + 1]);
        }

        AdminLog::record('reordered', 'modules');

        return back()->with('success', 'Orden de módulos actualizado.');
    }
}
