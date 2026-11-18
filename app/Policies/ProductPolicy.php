<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'support_admin', 'seller_owner'])
            || $user->can('products.view')
            || (bool) $user->seller;
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->hasRole(['super_admin', 'admin', 'support_admin']) || $user->can('products.view')) {
            return true;
        }

        return $user->seller !== null && $product->seller_id === $user->seller->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin'])
            || ($user->seller !== null && $user->can('products.create'));
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->hasRole(['super_admin', 'admin']) || $user->can('products.edit')) {
            return true;
        }

        return $user->seller !== null && $product->seller_id === $user->seller->id;
    }

    public function delete(User $user, Product $product): bool
    {
        if ($user->hasRole(['super_admin', 'admin']) || $user->can('products.delete')) {
            return true;
        }

        return $user->seller !== null && $product->seller_id === $user->seller->id;
    }

    public function approve(User $user, Product $product): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('products.approve');
    }

    public function reject(User $user, Product $product): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('products.approve');
    }

    public function block(User $user, Product $product): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('products.approve');
    }

    public function requestCorrection(User $user, Product $product): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('products.approve');
    }

    public function bulkApprove(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('products.approve');
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('products.edit');
    }

    public function export(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'support_admin']) || $user->can('products.view');
    }
}
