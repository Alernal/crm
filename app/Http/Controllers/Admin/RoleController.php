<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\AdminModule;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = AdminRole::withCount('permissions')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $modules = AdminModule::orderBy('order')->get();

        return view('admin.roles.create', compact('modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:64',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $role = AdminRole::create([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $this->syncPermissions($role, $request->input('permissions', []));
        AdminLog::record('created', 'roles', $role->id, [], $role->toArray());

        return redirect()->route('admin.roles.index')->with('success', "Rol \"{$role->name}\" creado.");
    }

    public function edit(AdminRole $role): View
    {
        $modules     = AdminModule::orderBy('order')->get();
        $permissions = $role->permissions->keyBy('module_key');

        return view('admin.roles.edit', compact('role', 'modules', 'permissions'));
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:64',
            'description' => 'nullable|string|max:255',
            'active'      => 'boolean',
            'permissions' => 'nullable|array',
        ]);

        $old = $role->toArray();
        $role->update([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'active'      => $request->boolean('active', true),
        ]);

        $this->syncPermissions($role, $request->input('permissions', []));
        AdminLog::record('updated', 'roles', $role->id, $old, $role->fresh()->toArray());

        return redirect()->route('admin.roles.index')->with('success', "Rol \"{$role->name}\" actualizado.");
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        AdminLog::record('deleted', 'roles', $role->id, $role->toArray(), []);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Rol eliminado.');
    }

    private function syncPermissions(AdminRole $role, array $permissions): void
    {
        $role->permissions()->delete();

        foreach ($permissions as $moduleKey => $actions) {
            AdminPermission::create([
                'role_id'    => $role->id,
                'module_key' => $moduleKey,
                'can_view'   => isset($actions['view']),
                'can_create' => isset($actions['create']),
                'can_edit'   => isset($actions['edit']),
                'can_delete' => isset($actions['delete']),
            ]);
        }
    }
}
