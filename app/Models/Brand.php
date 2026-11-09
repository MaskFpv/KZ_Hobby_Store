<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    use Searchable;
    use HasUniqueSlug;

    protected $fillable = ['name', 'slug', 'country', 'logo', 'description'];

    protected $searchableFields = ['name', 'country'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
