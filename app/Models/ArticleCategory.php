<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Kategori artikel (dikelola Admin Sosial Media dari panel). */
class ArticleCategory extends Model
{
    protected $fillable = ['name', 'slug', 'kanji', 'color', 'description', 'sort_order'];

    protected static function booted(): void
    {
        // Slug dibuat sekali saat dibuat (URL tetap walau nama diganti), dijamin unik
        static::creating(function (ArticleCategory $c) {
            $base = $c->slug ?: (Str::slug($c->name) ?: 'kategori');
            $c->slug = $base;
            for ($i = 2; static::where('slug', $c->slug)->exists(); $i++) {
                $c->slug = "{$base}-{$i}";
            }
        });
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function getKanjiOrDefaultAttribute(): string
    {
        return $this->kanji ?: '記';
    }
}
