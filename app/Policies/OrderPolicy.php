<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Pesanan online. update = proses & tandai siap (Gudang/CS),
 * delete = batalkan/refund (Keuangan/Admin). Serah terima pakai gate "handover-orders".
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list orders');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('view orders');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('update orders');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('delete orders');
    }
}
