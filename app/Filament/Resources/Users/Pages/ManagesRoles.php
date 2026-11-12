<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Validation\ValidationException;

/** Penyimpanan role & status aktif dengan pengecekan eskalasi hak akses di server. */
trait ManagesRoles
{
    protected function applyRolesAndStatus(User $user, array $data): void
    {
        $actor = auth()->user();
        $requested = array_values(array_intersect((array) ($data['role_names'] ?? []), array_keys(UserResource::assignableRoles())));

        if (count($requested) !== count((array) ($data['role_names'] ?? []))) {
            throw ValidationException::withMessages(['data.role_names' => 'Anda tidak berhak memberikan salah satu role yang dipilih.']);
        }
        // Super admin terakhir tidak boleh kehilangan role-nya / dinonaktifkan.
        $losingSuper = $user->exists && $user->isSuperAdmin() && (! in_array(Roles::SUPER_ADMIN, $requested, true) || ! ($data['is_active'] ?? true));
        if ($losingSuper && User::role(Roles::SUPER_ADMIN)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['data.role_names' => 'Harus tetap ada minimal satu Super Admin aktif.']);
        }
        if ($user->is($actor) && ! ($data['is_active'] ?? true)) {
            throw ValidationException::withMessages(['data.is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $before = $user->exists ? $user->getRoleNames()->all() : [];
        $user->forceFill(['is_active' => (bool) ($data['is_active'] ?? true)])->save();
        $user->syncRoles($requested);

        if ($before !== $requested) {
            ActivityLog::record('user.roles_changed', $user, null, ['from' => $before, 'to' => $requested]);
        }
    }
}
