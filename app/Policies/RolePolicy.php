<?php

namespace App\Policies;

use App\Models\User;

class RolePolicy
{
    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        if ($user->can('Role:viewAny')) {
            return true;
        }
        return false;
    }

    public function view(User $user): bool
    {
        if ($user->can('Role:view')) {
            return true;
        }
        return false;
    }

    public function create(User $user): bool
    {
        if ($user->can('Role:create')) {
            return true;
        }
        return false;
    }

    public function update(User $user): bool
    {
        if ($user->can('Role:update')) {
            return true;
        }
        return false;
    }

    public function delete(User $user): bool
    {
        if ($user->can('Role:delete')) {
            return true;
        }
        return false;
    }
}
