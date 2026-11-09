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
}
