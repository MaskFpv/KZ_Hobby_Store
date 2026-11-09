<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role & hak aksesnya. Role "super-admin" dikunci (nama tidak bisa diubah,
 * tidak bisa dihapus) karena dipakai Gate::before di AuthServiceProvider.
 */
class RoleController extends Controller
{
    public const LOCKED = ['super-admin'];

    public function index(): View
    {
        $this->authorize('list', Role::class);

        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('app.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('app.roles.create', ['permissionGroups' => PermissionController::grouped()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9\-]+$/', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], ['name.regex' => 'Nama role hanya huruf kecil, angka, dan strip. Contoh: kasir-senior']);

        $role = Role::create(['name' => $data['name']]);
        $role->syncPermissions(Permission::whereIn('id', $data['permissions'] ?? [])->get());

        return redirect()->route('roles.index')->withSuccess(__('crud.common.created'));
    }

    public function show(Role $role): RedirectResponse
    {
        return redirect()->route('roles.edit', $role);
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('app.roles.edit', [
            'role' => $role,
            'permissionGroups' => PermissionController::grouped(),
            'locked' => in_array($role->name, self::LOCKED, true),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $locked = in_array($role->name, self::LOCKED, true);

        $data = $request->validate([
            'name' => $locked ? ['nullable'] : ['required', 'string', 'max:32', 'regex:/^[a-z0-9\-]+$/', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], ['name.regex' => 'Nama role hanya huruf kecil, angka, dan strip.']);

        if (! $locked) {
            $role->update(['name' => $data['name']]);
        }

        // super-admin selalu memegang semua permission
        $role->syncPermissions($locked
            ? Permission::all()
            : Permission::whereIn('id', $data['permissions'] ?? [])->get());

        return redirect()->route('roles.index')->withSuccess(__('crud.common.saved'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if (in_array($role->name, self::LOCKED, true)) {
            return redirect()->route('roles.index')->with('error', "Role {$role->name} adalah role sistem dan tidak bisa dihapus.");
        }
        if ($role->users()->exists()) {
            return redirect()->route('roles.index')->with('error', "Role {$role->name} masih dipakai user. Pindahkan user ke role lain dulu.");
        }

        $role->delete();

        return redirect()->route('roles.index')->withSuccess(__('crud.common.removed'));
    }
}
