<?php

namespace App\Policies;

use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockOpnamePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list stockopnames');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockOpname $model): bool
    {
        return $user->hasPermissionTo('view stockopnames');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create stockopnames');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StockOpname $model): bool
    {
        return $user->hasPermissionTo('update stockopnames');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockOpname $model): bool
    {
        return $user->hasPermissionTo('delete stockopnames');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete stockopnames');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockOpname $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockOpname $model): bool
    {
        return false;
    }
}
