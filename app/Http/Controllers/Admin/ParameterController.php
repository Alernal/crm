<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\GlobalParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParameterController extends Controller
{
    public function index(): View
    {
        $parameters = GlobalParameter::orderBy('group')->orderBy('label')->get()->groupBy('group');

        return view('admin.parameters.index', compact('parameters'));
    }

    public function update(Request $request, GlobalParameter $param): RedirectResponse
    {
        if (! $param->editable) {
            return back()->withErrors(['value' => 'Este parámetro no es editable.']);
        }

        $request->validate(['value' => 'nullable|string|max:1000']);

        $old = ['value' => $param->value];
        $param->update(['value' => $request->input('value')]);
        AdminLog::record('updated', 'parameters', $param->id, $old, ['value' => $param->value]);

        return back()->with('success', "Parámetro \"{$param->label}\" actualizado.");
    }
}
