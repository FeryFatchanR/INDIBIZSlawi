<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Actions\Crm\CreateLead as CreateLeadAction;
use App\Enums\LeadSource;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $source = LeadSource::tryFrom((string) ($data['source'] ?? '')) ?? LeadSource::Manual;

        return app(CreateLeadAction::class)->execute($data, $source, [], auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return LeadResource::getUrl('view', ['record' => $this->record]);
    }
}
