<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Pelanggan default untuk pembeli tanpa member (walk-in)
        Customer::firstOrCreate(
            ['kode_member' => 'MBR00000'],
            ['nama' => 'Pelanggan Umum', 'alamat' => '-']
        );

        $members = [
            ['nama' => 'Rizky Pratama', 'email' => 'rizky@example.com', 'telepon' => '081234567801', 'alamat' => 'Bandung'],
            ['nama' => 'Ayu Lestari', 'email' => 'ayu@example.com', 'telepon' => '081234567802', 'alamat' => 'Jakarta Selatan'],
            ['nama' => 'Dimas Saputra', 'email' => 'dimas@example.com', 'telepon' => '081234567803', 'alamat' => 'Surabaya'],
        ];

        foreach ($members as $member) {
            Customer::firstOrCreate(['email' => $member['email']], $member);
        }
    }
}
