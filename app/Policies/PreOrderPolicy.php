<?php

namespace App\Policies;

use App\Models\PreOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PreOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list preorders');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PreOrder $model): bool
    {
        return $user->hasPermissionTo('view preorders');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create preorders');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PreOrder $model): bool
    {
        return $user->hasPermissionTo('update preorders');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PreOrder $model): bool
    {
        return $user->hasPermissionTo('delete preorders');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete preorders');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PreOrder $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PreOrder $model): bool
    {
        return false;
    }
}
