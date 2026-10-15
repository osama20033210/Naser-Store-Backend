<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewOwn(User $user, Order $order): bool
    {
        return $user->account_type === 'customer' && $user->customer?->id === $order->customer_id;
    }

    public function cancelOwn(User $user, Order $order): bool
    {
        return $this->viewOwn($user, $order) && $order->channel === 'storefront' && $order->status === OrderStatus::Pending;
    }

    public function recordPayment(User $user, Order $order): bool
    {
        return $user->can('orders.manage-payment');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->can('orders.update-status');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.cancel');
    }
}
