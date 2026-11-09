<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Isi kolom `slug` otomatis dari `name` dan pastikan unik.
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::saving(function ($model) {
            if (empty($model->slug) || $model->isDirty('name')) {
                $model->slug = static::uniqueSlug($model->name, $model->getKey());
            }
        });
    }

    public static function uniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
