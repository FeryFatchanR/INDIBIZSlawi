<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;

/** Manajemen user dengan proteksi eskalasi hak akses. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        // Hanya Super Admin yang boleh mengubah akun Super Admin lain.
        return $user->can('users.manage') && (! $model->isSuperAdmin() || $user->isSuperAdmin());
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('users.manage') || $user->is($model)) {
            return false;
        }
        if ($model->isSuperAdmin()) {
            return $user->isSuperAdmin() && User::role(Roles::SUPER_ADMIN)->count() > 1;
        }

        return true;
    }

    public function deleteAny(User $user): bool
    {
        return false; // hapus massal user dinonaktifkan
    }
}
