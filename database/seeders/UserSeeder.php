<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun default untuk development. Ganti password sebelum production.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin Toko', 'password' => Hash::make('admin123')]
        );
        $admin->syncRoles(['super-admin']);

        $kasir = User::updateOrCreate(
            ['email' => 'kasir@gmail.com'],
            ['name' => 'Kasir Toko', 'password' => Hash::make('kasir123')]
        );
        $kasir->syncRoles(['kasir']);
    }
}
