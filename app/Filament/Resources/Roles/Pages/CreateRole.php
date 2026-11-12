<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    use SyncsPermissions;

    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $this->syncPermissions($role, $data);

            return $role;
        });
    }
}
