<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Subjek permission. Nama permission = "<aksi> <subjek>",
     * contoh "list products". Harus sama dengan yang dicek di app/Policies.
     */
    private const SUBJECTS = [
        'categories',
        'brands',
        'products',
        'customers',
        'stockins',
        'stockopnames',
        'transactions',
        'preorders',
        // khusus admin
        'roles',
        'permissions',
        'users',
    ];

    private const ACTIONS = ['list', 'view', 'create', 'update', 'delete'];

    /** Hak akses kasir. Super-admin otomatis lolos semua cek (Gate::before). */
    private const KASIR_PERMISSIONS = [
        'view dashboard',
        'list categories', 'view categories',
        'list brands', 'view brands',
        'list products', 'view products',
        'list customers', 'view customers', 'create customers', 'update customers',
        'list transactions', 'view transactions', 'create transactions',
        'list preorders', 'view preorders', 'create preorders', 'update preorders',
        'list stockins', 'view stockins',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('view dashboard');

        foreach (self::SUBJECTS as $subject) {
            foreach (self::ACTIONS as $action) {
                Permission::findOrCreate("{$action} {$subject}");
            }
        }

        $kasir = Role::findOrCreate('kasir');
        $kasir->syncPermissions(self::KASIR_PERMISSIONS);

        $admin = Role::findOrCreate('super-admin');
        $admin->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
