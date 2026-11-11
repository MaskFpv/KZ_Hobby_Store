<?php

namespace App\Policies;

use App\Models\User;

/** Pembayaran online (read-only di panel). */
class OnlinePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list payments');
    }
}
