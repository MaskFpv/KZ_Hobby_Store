<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/** Artikel website: dikelola Admin Sosial Media, Admin, Super Admin. */
class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list articles');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->hasPermissionTo('view articles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create articles');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->hasPermissionTo('update articles');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->hasPermissionTo('delete articles');
    }
}
