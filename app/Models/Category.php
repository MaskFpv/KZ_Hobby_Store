<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    use Searchable;
    use HasUniqueSlug;

    protected $fillable = ['name', 'slug', 'description'];

    protected $searchableFields = ['name', 'description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** [kata kunci slug, kanji ilustrasi, nama Jepang] untuk tampilan website. */
    private const JP = [
        ['figure', '像', 'フィギュア'],
        ['gunpla', '模', 'ガンプラ'],
        ['manga', '漫', '漫画'],
        ['apparel', '服', 'アパレル'],
        ['access', '飾', '小物'],
    ];

    public function getKanjiAttribute(): string
    {
        return $this->jpMatch()[1] ?? '品';
    }

    public function getJpNameAttribute(): string
    {
        return $this->jpMatch()[2] ?? 'コレクション';
    }

    private function jpMatch(): ?array
    {
        foreach (self::JP as $row) {
            if (str_contains((string) $this->slug, $row[0])) {
                return $row;
            }
        }

        return null;
    }
}
