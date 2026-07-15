<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminCustomField;
use App\Models\AdminLog;
use App\Models\AdminModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldController extends Controller
{
    public function index(Request $request): View
    {
        $modules     = AdminModule::orderBy('order')->get();
        $moduleKey   = $request->get('module');
        $fieldsQuery = AdminCustomField::with('module')->orderBy('module_key')->orderBy('order');

        if ($moduleKey) {
            $fieldsQuery->where('module_key', $moduleKey);
        }

        $fields = $fieldsQuery->get();

        return view('admin.fields.index', compact('fields', 'modules', 'moduleKey'));
    }

    public function create(): View
    {
        $modules = AdminModule::where('active', true)->orderBy('order')->get();
        $types   = AdminCustomField::$types;

        return view('admin.fields.create', compact('modules', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'module_key'       => 'required|string|exists:admin_modules,key',
            'name'             => 'required|string|max:64|alpha_dash',
            'label'            => 'required|string|max:128',
            'type'             => 'required|in:' . implode(',', array_keys(AdminCustomField::$types)),
            'required'         => 'boolean',
            'visible'          => 'boolean',
            'order'            => 'nullable|integer|min:0',
            'default_value'    => 'nullable|string|max:255',
            'validation_rules' => 'nullable|array',
            'validation_rules.min'     => 'nullable|numeric',
            'validation_rules.max'     => 'nullable|numeric',
            'validation_rules.regex'   => 'nullable|string',
            'validation_rules.options' => 'nullable|string',
        ]);

        $data['required']         = $request->boolean('required');
        $data['visible']          = $request->boolean('visible', true);
        $data['validation_rules'] = $this->parseValidationRules($request);

        $field = AdminCustomField::create($data);
        AdminLog::record('created', 'fields', $field->id, [], $field->toArray());

        return redirect()->route('admin.fields.index', ['module' => $field->module_key])
            ->with('success', "Campo \"{$field->label}\" creado.");
    }

    public function edit(AdminCustomField $field): View
    {
        $modules = AdminModule::where('active', true)->orderBy('order')->get();
        $types   = AdminCustomField::$types;

        return view('admin.fields.edit', compact('field', 'modules', 'types'));
    }

    public function update(Request $request, AdminCustomField $field): RedirectResponse
    {
        $data = $request->validate([
            'module_key'       => 'required|string|exists:admin_modules,key',
            'name'             => 'required|string|max:64|alpha_dash',
            'label'            => 'required|string|max:128',
            'type'             => 'required|in:' . implode(',', array_keys(AdminCustomField::$types)),
            'required'         => 'boolean',
            'visible'          => 'boolean',
            'order'            => 'nullable|integer|min:0',
            'default_value'    => 'nullable|string|max:255',
            'validation_rules' => 'nullable|array',
        ]);

        $old = $field->toArray();
        $data['required']         = $request->boolean('required');
        $data['visible']          = $request->boolean('visible', true);
        $data['validation_rules'] = $this->parseValidationRules($request);

        $field->update($data);
        AdminLog::record('updated', 'fields', $field->id, $old, $field->fresh()->toArray());

        return redirect()->route('admin.fields.index', ['module' => $field->module_key])
            ->with('success', "Campo \"{$field->label}\" actualizado.");
    }

    public function destroy(AdminCustomField $field): RedirectResponse
    {
        $moduleKey = $field->module_key;
        AdminLog::record('deleted', 'fields', $field->id, $field->toArray(), []);
        $field->delete();

        return redirect()->route('admin.fields.index', ['module' => $moduleKey])
            ->with('success', 'Campo eliminado.');
    }

    public function toggle(AdminCustomField $field): RedirectResponse
    {
        $old = ['active' => $field->active];
        $field->update(['active' => ! $field->active]);
        AdminLog::record('toggled', 'fields', $field->id, $old, ['active' => $field->active]);

        return back()->with('success', 'Campo ' . ($field->active ? 'activado' : 'desactivado') . '.');
    }

    private function parseValidationRules(Request $request): ?array
    {
        $rules = array_filter([
            'min'   => $request->input('validation_rules.min'),
            'max'   => $request->input('validation_rules.max'),
            'regex' => $request->input('validation_rules.regex'),
        ]);

        $options = $request->input('validation_rules.options');
        if ($options) {
            $rules['options'] = array_map('trim', explode("\n", $options));
        }

        return $rules ?: null;
    }
}
