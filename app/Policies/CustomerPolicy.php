<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the customer can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list customers');
    }

    /**
     * Determine whether the customer can view the model.
     */
    public function view(User $user, Customer $model): bool
    {
        return $user->hasPermissionTo('view customers');
    }

    /**
     * Determine whether the customer can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create customers');
    }

    /**
     * Determine whether the customer can update the model.
     */
    public function update(User $user, Customer $model): bool
    {
        return $user->hasPermissionTo('update customers');
    }

    /**
     * Determine whether the customer can delete the model.
     */
    public function delete(User $user, Customer $model): bool
    {
        return $user->hasPermissionTo('delete customers');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete customers');
    }

    /**
     * Determine whether the customer can restore the model.
     */
    public function restore(User $user, Customer $model): bool
    {
        return false;
    }

    /**
     * Determine whether the customer can permanently delete the model.
     */
    public function forceDelete(User $user, Customer $model): bool
    {
        return false;
    }
}
