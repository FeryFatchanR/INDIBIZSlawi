<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LeadResource::changeStatusAction(),
            LeadResource::assignAction(),
            LeadResource::addNoteAction(),
            EditAction::make(),
        ];
    }
}
