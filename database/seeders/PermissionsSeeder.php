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
        'orders',     // pesanan online
        'payments',   // pembayaran online
        'chats',      // chat pelanggan
        // khusus admin
        'roles',
        'permissions',
        'users',
    ];

    private const ACTIONS = ['list', 'view', 'create', 'update', 'delete'];

    /** Permission di luar pola CRUD. */
    private const SPECIAL = [
        'view dashboard',   // menu Dashboard
        'view reports',     // Laporan Penjualan, grafik penjualan, export transaksi
        'view profit',      // harga modal & laba kotor
        'handover orders',  // serah terima pesanan online (status "diambil")
    ];

    /**
     * Hak akses per role. Super-admin otomatis lolos semua cek (Gate::before)
     * dan tetap diberi semua permission agar terlihat jelas di menu Role.
     * '*' = semua aksi CRUD untuk subjek itu.
     */
    private const MATRIX = [
        'admin' => [
            'special' => ['view dashboard', 'view reports', 'view profit', 'handover orders'],
            'categories' => '*', 'brands' => '*', 'products' => '*', 'customers' => '*',
            'stockins' => '*', 'stockopnames' => '*', 'transactions' => '*', 'preorders' => '*',
            'orders' => '*', 'payments' => '*', 'chats' => '*',
            'users' => ['list', 'view'],
        ],
        'keuangan' => [
            'special' => ['view dashboard', 'view reports', 'view profit'],
            'categories' => ['list', 'view'], 'brands' => ['list', 'view'], 'products' => ['list', 'view'],
            'customers' => ['list', 'view'],
            'stockins' => ['list', 'view'],
            'transactions' => ['list', 'view'],
            'preorders' => ['list', 'view', 'update', 'delete'], // delete = batal/refund
            'orders' => ['list', 'view', 'delete'],              // delete = batal/refund
            'payments' => ['list', 'view'],
        ],
        'gudang' => [
            'special' => ['view dashboard'],
            'categories' => '*', 'brands' => '*', 'products' => '*',
            'stockins' => '*', 'stockopnames' => '*',
            'preorders' => ['list', 'view'],
            'orders' => ['list', 'view', 'update'],
        ],
        'cs' => [
            'special' => ['view dashboard', 'handover orders'],
            'categories' => ['list', 'view'], 'brands' => ['list', 'view'], 'products' => ['list', 'view'],
            'customers' => ['list', 'view', 'create', 'update'],
            'preorders' => ['list', 'view', 'create', 'update'],
            'orders' => ['list', 'view', 'update'],
            'payments' => ['list', 'view'],
            'chats' => '*',
        ],
        // Kasir murni penjualan: tanpa dashboard, laporan, grafik, dan stok.
        'kasir' => [
            'special' => ['handover orders'],
            'categories' => ['list', 'view'], 'brands' => ['list', 'view'], 'products' => ['list', 'view'],
            'customers' => ['list', 'view', 'create', 'update'],
            'transactions' => ['list', 'view', 'create'],
            'preorders' => ['list', 'view', 'create', 'update'],
            'orders' => ['list', 'view'],
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::SPECIAL as $name) {
            Permission::findOrCreate($name);
        }
        foreach (self::SUBJECTS as $subject) {
            foreach (self::ACTIONS as $action) {
                Permission::findOrCreate("{$action} {$subject}");
            }
        }

        foreach (self::MATRIX as $role => $rules) {
            Role::findOrCreate($role)->syncPermissions(self::expand($rules));
        }

        Role::findOrCreate('super-admin')->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** ['products' => '*', 'special' => [...]] -> daftar nama permission. */
    private static function expand(array $rules): array
    {
        $names = $rules['special'] ?? [];
        unset($rules['special']);

        foreach ($rules as $subject => $actions) {
            foreach ($actions === '*' ? self::ACTIONS : $actions as $action) {
                $names[] = "{$action} {$subject}";
            }
        }

        return $names;
    }
}
