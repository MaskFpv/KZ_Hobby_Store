<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun default untuk development, satu per role. Ganti password sebelum production.
     * Format: [email, nama, password, role]
     */
    private const USERS = [
        ['admin@gmail.com', 'Admin Toko', 'admin123', 'super-admin'],
        ['manajer@gmail.com', 'Manajer Toko', 'manajer123', 'admin'],
        ['keuangan@gmail.com', 'Keuangan Toko', 'keuangan123', 'keuangan'],
        ['gudang@gmail.com', 'Gudang Toko', 'gudang123', 'gudang'],
        ['cs@gmail.com', 'CS Toko', 'cstoko123', 'cs'],
        ['kasir@gmail.com', 'Kasir Toko', 'kasir123', 'kasir'],
    ];

    public function run(): void
    {
        foreach (self::USERS as [$email, $name, $password, $role]) {
            User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make($password)])
                ->syncRoles([$role]);
        }
    }
}
