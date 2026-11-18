<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'support_admin'])
            || $user->can('users.view');
    }

    /**
     * Determine whether the user can view the customer detail.
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->hasRole(['super_admin', 'admin', 'support_admin']) || $user->can('users.view')) {
            return true;
        }

        return $customer->user_id === $user->id;
    }

    /**
     * Determine whether the user can restrict the customer account.
     */
    public function restrict(User $user, Customer $customer): bool
    {
        return $user->hasRole(['super_admin', 'admin'])
            || $user->can('users.edit');
    }

    /**
     * Determine whether the user can reactivate the customer account.
     */
    public function reactivate(User $user, Customer $customer): bool
    {
        return $user->hasRole(['super_admin', 'admin'])
            || $user->can('users.edit');
    }

    /**
     * Determine whether the user can review suspicious activity and add notes.
     */
    public function reviewSuspicious(User $user, Customer $customer): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'support_admin'])
            || $user->can('users.edit');
    }

    /**
     * Determine whether the user can export customer reports.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin'])
            || $user->can('users.view');
    }

    /**
     * Determine whether the user can view unmasked sensitive personal and financial details.
     */
    public function viewSensitive(User $user, ?Customer $customer = null): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'finance_manager'])
            || $user->can('users.edit');
    }
}
