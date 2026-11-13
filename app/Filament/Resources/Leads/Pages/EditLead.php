<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), RestoreAction::make()];
    }

    /** Status & assignment tidak diubah dari form ini (melalui aksi khusus yang tercatat). */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['status'], $data['assigned_to'], $data['won_at']);

        return $data;
    }
}
