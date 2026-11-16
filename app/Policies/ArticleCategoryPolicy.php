<?php

namespace App\Policies;

use App\Models\ArticleCategory;
use App\Models\User;

/** Kategori artikel mengikuti izin artikel (subjek "articles"). */
class ArticleCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list articles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create articles');
    }

    public function update(User $user, ArticleCategory $category): bool
    {
        return $user->hasPermissionTo('update articles');
    }

    public function delete(User $user, ArticleCategory $category): bool
    {
        return $user->hasPermissionTo('delete articles');
    }
}
