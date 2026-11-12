<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Models\ActivityLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait SyncsPermissions
{
    protected function syncPermissions(Role $role, array $data): void
    {
        $selected = collect($data['permission_groups'] ?? [])->flatten()->filter(fn ($p) => is_string($p))->values();
        $valid = Permission::query()->whereIn('name', $selected)->pluck('name')->all(); // hanya permission yang ada
        // Permission sistem sensitif hanya dapat diberikan oleh Super Admin.
        if (! auth()->user()->isSuperAdmin()) {
            $valid = array_values(array_diff($valid, ['users.manage', 'roles.manage', 'settings.custom_scripts', 'builder.custom_html']));
        }
        $role->syncPermissions($valid);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ActivityLog::record('role.permissions_synced', $role, "Role {$role->name}", ['count' => count($valid)]);
    }

    protected function permissionGroupsFor(Role $role): array
    {
        return $role->permissions->pluck('name')->groupBy(fn ($n) => explode('.', $n)[0])->map->values()->all();
    }
}
