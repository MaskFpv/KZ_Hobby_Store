<?php

namespace App\Models\Concerns;

/**
 * Membuat kode dokumen harian berurutan, contoh: PO-20261109-0001.
 * Model wajib punya kolom `code` dan konstanta CODE_PREFIX.
 */
trait GeneratesDailyCode
{
    public static function bootGeneratesDailyCode(): void
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = static::CODE_PREFIX . '-' . now()->format('Ymd') . '-';

        $last = static::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
