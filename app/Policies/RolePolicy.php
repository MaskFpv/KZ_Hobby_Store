<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/** Policy untuk Spatie Role (ditemukan otomatis lewat Gate::guessPolicyNamesUsing). */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list roles');
    }

    public function list(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('view roles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('update roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('delete roles');
    }
}
