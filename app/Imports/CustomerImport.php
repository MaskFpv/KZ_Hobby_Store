<?php

namespace App\Imports;

use App\Models\Customer;

/**
 * Header: nama* , email, telepon, alamat.
 * Email yang sudah terdaftar -> data member diperbarui. kode_member dibuat otomatis.
 */
class CustomerImport extends BaseRowImport
{
    protected function handleRow(array $row): string
    {
        $nama = $this->pick($row, ['nama', 'nama_pelanggan', 'name']);
        if (! $nama) {
            throw new \InvalidArgumentException('kolom nama kosong.');
        }

        $email = $this->pick($row, ['email']);
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("email \"{$email}\" tidak valid.");
        }

        $telepon = $this->pick($row, ['telepon', 'no_hp', 'hp', 'phone']);
        $data = [
            'nama' => mb_substr($nama, 0, 255),
            'email' => $email,
            'telepon' => $telepon !== null ? mb_substr((string) $telepon, 0, 30) : null,
            'alamat' => $this->pick($row, ['alamat', 'address']),
        ];

        $existing = $email ? Customer::where('email', $email)->first() : null;

        if ($existing) {
            $existing->update(array_filter($data, fn ($v) => $v !== null));

            return 'updated';
        }

        Customer::create($data);

        return 'created';
    }
}
