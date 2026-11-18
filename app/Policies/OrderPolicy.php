<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'support_admin']) || $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole(['super_admin', 'admin', 'support_admin']) || $user->can('orders.view')) {
            return true;
        }

        return $order->customer_id === $user->customer?->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->hasRole(['super_admin', 'admin']) || $user->can('orders.cancel')) {
            return true;
        }

        return $order->customer_id === $user->customer?->id;
    }

    public function override(User $user, Order $order): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('orders.edit');
    }

    public function export(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'support_admin']) || $user->can('orders.view');
    }
}
