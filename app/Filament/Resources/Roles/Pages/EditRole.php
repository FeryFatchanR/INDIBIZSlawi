<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditRole extends EditRecord
{
    use SyncsPermissions;

    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permission_groups'] = $this->permissionGroupsFor($this->record->load('permissions'));

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            if (isset($data['name'])) {
                $record->update(['name' => $data['name']]);
            }
            $this->syncPermissions($record, $data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
