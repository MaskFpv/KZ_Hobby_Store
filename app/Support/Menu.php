<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * Membaca config/menu.php dan menyaring item sesuai route & izin user.
 */
class Menu
{
    /** Grup menu yang boleh dilihat user (grup kosong dibuang). */
    public static function forUser(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        $groups = [];

        foreach (config('menu', []) as $group) {
            $items = array_values(array_filter($group['items'], fn ($item) => self::visible($item, $user)));

            if ($items) {
                $groups[] = ['title' => $group['title'], 'items' => array_map(fn ($i) => $i + ['is_active' => self::isActive($i)], $items)];
            }
        }

        return $groups;
    }

    /** Item menu yang sedang aktif (untuk judul halaman di topbar). */
    public static function current(): ?array
    {
        foreach (config('menu', []) as $group) {
            foreach ($group['items'] as $item) {
                if (self::isActive($item)) {
                    return $item + ['group' => $group['title']];
                }
            }
        }

        return null;
    }

    /** Daftar datar item yang boleh dilihat user. */
    public static function flat(?Authenticatable $user): array
    {
        return array_merge(...array_map(fn ($g) => $g['items'], self::forUser($user)) ?: [[]]);
    }

    private static function visible(array $item, Authenticatable $user): bool
    {
        if (! Route::has($item['route'])) {
            return false;
        }

        if (empty($item['can'])) {
            return true;
        }

        [$ability, $arguments] = [$item['can'][0], $item['can'][1] ?? []];

        return $user->can($ability, $arguments);
    }

    private static function isActive(array $item): bool
    {
        return request()->routeIs(...(array) ($item['active'] ?? $item['route']));
    }
}
