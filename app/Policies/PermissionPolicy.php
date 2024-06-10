<?php

namespace App\Policies;

use App\Models\User;

class PermissionPolicy
{
    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        if ($user->can('Permission:viewAny')) {
            return true;
        }
        return false;
    }

    public function view(User $user): bool
    {
        if ($user->can('Permission:view')) {
            return true;
        }
        return false;
    }

    public function create(User $user): bool
    {
        if ($user->can('Permission:create')) {
            return true;
        }
        return false;
    }

    public function update(User $user): bool
    {
        if ($user->can('Permission:update')) {
            return true;
        }
        return false;
    }

    public function delete(User $user): bool
    {
        if ($user->can('Permission:delete')) {
            return true;
        }
        return false;
    }
}
