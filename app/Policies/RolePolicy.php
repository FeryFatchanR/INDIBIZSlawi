<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    /** Role super_admin bersifat tetap (akses penuh via Gate::before). */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage') && $role->name !== Roles::SUPER_ADMIN;
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.manage') && ! in_array($role->name, Roles::PANEL_ROLES, true);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
