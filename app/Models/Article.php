<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Artikel website. `body` berisi HTML yang sudah dibersihkan (App\Support\HtmlSanitizer). */
class Article extends Model
{
    public const STATUSES = ['draft' => 'Draf', 'published' => 'Terbit'];

    protected $fillable = ['title', 'slug', 'article_category_id', 'excerpt', 'body', 'cover', 'status', 'is_pinned', 'published_at', 'user_id'];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
        'is_pinned' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            $article->slug = $article->slug ?: static::uniqueSlug($article->title);
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'artikel';
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    /** Sudah terbit dan waktu terbitnya sudah lewat. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function isPublic(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->isPast();
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category->name ?? 'Umum';
    }

    public function getCategoryColorAttribute(): string
    {
        return $this->category->color ?? '#ff2e88';
    }

    public function getCategoryKanjiAttribute(): string
    {
        return $this->category->kanji_or_default ?? '記';
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover ? asset('storage/' . $this->cover) : null;
    }

    /** Perkiraan menit baca (± 200 kata per menit). */
    public function getReadingMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 200));
    }

    /** Ringkasan: excerpt bila ada, selain itu potongan isi. */
    public function getSummaryAttribute(): string
    {
        return $this->excerpt ?: Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->body))), 160);
    }
}
