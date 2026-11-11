<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permission. Permission bawaan sistem (dipakai app/Policies, format "<aksi> <subjek>")
 * tidak bisa diganti nama / dihapus agar hak akses tidak rusak diam-diam.
 */
class PermissionController extends Controller
{
    /** Label subjek untuk tampilan yang lebih ramah. */
    public const SUBJECT_LABELS = [
        'dashboard' => 'Dashboard',
        'categories' => 'Kategori',
        'brands' => 'Brand',
        'products' => 'Produk',
        'customers' => 'Pelanggan',
        'stockins' => 'Stok Masuk',
        'stockopnames' => 'Stok Opname',
        'transactions' => 'Transaksi',
        'preorders' => 'Pre-Order',
        'orders' => 'Pesanan Online',
        'payments' => 'Pembayaran Online',
        'chats' => 'Chat Pelanggan',
        'reports' => 'Laporan',
        'profit' => 'Laba & Harga Modal',
        'users' => 'User',
        'roles' => 'Role',
        'permissions' => 'Permission',
    ];

    public static function isSystem(Permission $permission): bool
    {
        [, $subject] = array_pad(explode(' ', $permission->name, 2), 2, null);

        return array_key_exists((string) $subject, self::SUBJECT_LABELS);
    }

    /** Permission dikelompokkan per subjek: ['Produk' => Collection, ...] */
    public static function grouped(): Collection
    {
        return Permission::orderBy('id')->get()->groupBy(function ($p) {
            $subject = explode(' ', $p->name, 2)[1] ?? $p->name;

            return self::SUBJECT_LABELS[$subject] ?? ucfirst($subject);
        });
    }

    public function index(): View
    {
        $this->authorize('list', Permission::class);

        $permissions = Permission::with('roles:id,name')->orderBy('id')->get();

        return view('app.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        $this->authorize('create', Permission::class);

        return view('app.permissions.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Permission::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:permissions,name'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $permission = Permission::create(['name' => $data['name']]);
        $permission->syncRoles(Role::whereIn('id', $data['roles'] ?? [])->get());

        return redirect()->route('permissions.index')->withSuccess(__('crud.common.created'));
    }

    public function show(Permission $permission): RedirectResponse
    {
        return redirect()->route('permissions.edit', $permission);
    }

    public function edit(Permission $permission): View
    {
        $this->authorize('update', $permission);

        return view('app.permissions.edit', [
            'permission' => $permission,
            'roles' => Role::orderBy('name')->get(),
            'system' => self::isSystem($permission),
        ]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $this->authorize('update', $permission);

        $system = self::isSystem($permission);

        $data = $request->validate([
            'name' => $system ? ['nullable'] : ['required', 'string', 'max:64', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        if (! $system) {
            $permission->update(['name' => $data['name']]);
        }

        // super-admin selalu punya semua permission
        $roleIds = collect($data['roles'] ?? [])
            ->merge(Role::whereIn('name', RoleController::LOCKED)->pluck('id'))
            ->unique();
        $permission->syncRoles(Role::whereIn('id', $roleIds)->get());

        return redirect()->route('permissions.index')->withSuccess(__('crud.common.saved'));
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('delete', $permission);

        if (self::isSystem($permission)) {
            return redirect()->route('permissions.index')
                ->with('error', "Permission \"{$permission->name}\" dipakai sistem dan tidak bisa dihapus.");
        }

        $permission->delete();

        return redirect()->route('permissions.index')->withSuccess(__('crud.common.removed'));
    }
}
