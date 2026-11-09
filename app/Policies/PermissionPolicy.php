<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

/** Policy untuk Spatie Permission (ditemukan otomatis lewat Gate::guessPolicyNamesUsing). */
class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list permissions');
    }

    public function list(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('view permissions');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('update permissions');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('delete permissions');
    }
}
