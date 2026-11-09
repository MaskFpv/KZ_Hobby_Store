<?php

namespace App\Policies;

use App\Models\StockIn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockInPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list stockins');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockIn $model): bool
    {
        return $user->hasPermissionTo('view stockins');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create stockins');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StockIn $model): bool
    {
        return $user->hasPermissionTo('update stockins');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockIn $model): bool
    {
        return $user->hasPermissionTo('delete stockins');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete stockins');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockIn $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockIn $model): bool
    {
        return false;
    }
}
