<?php

namespace App\Support;

/**
 * Nama tampilan & deskripsi role. Hak akses tiap role diatur di
 * database/seeders/PermissionsSeeder.php.
 */
class Roles
{
    public const SUPER_ADMIN = 'super-admin';

    public const LABELS = [
        'super-admin' => 'Super Admin',
        'admin' => 'Admin',
        'keuangan' => 'Keuangan',
        'gudang' => 'Gudang',
        'cs' => 'CS / Order Online',
        'kasir' => 'Kasir',
    ];

    public const DESCRIPTIONS = [
        'super-admin' => 'Pemilik. Akses penuh termasuk user, role, dan permission.',
        'admin' => 'Operasional toko penuh, tanpa pengaturan akses.',
        'keuangan' => 'Laporan, laba, pembayaran online, pembatalan & refund.',
        'gudang' => 'Produk, stok masuk, opname, menyiapkan pesanan online.',
        'cs' => 'Pesanan online, chat pelanggan, member, pre-order.',
        'kasir' => 'Kasir (POS), pre-order di toko, member, serah terima pesanan.',
    ];

    public static function label(?string $role): string
    {
        return self::LABELS[$role] ?? ucfirst((string) ($role ?: 'User'));
    }

    public static function description(?string $role): string
    {
        return self::DESCRIPTIONS[$role] ?? 'Hak akses sesuai pengaturan role.';
    }
}
