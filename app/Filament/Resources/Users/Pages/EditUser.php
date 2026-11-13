<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    use ManagesRoles;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role_names'] = $this->record->getRoleNames()->all();
        $data['is_active'] = $this->record->is_active;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->fill(collect($data)->only(['name', 'email', 'phone', 'password'])->all())->save();
            $this->applyRolesAndStatus($record, $data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
