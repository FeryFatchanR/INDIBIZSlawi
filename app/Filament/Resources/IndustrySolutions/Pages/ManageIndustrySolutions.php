<?php

namespace App\Filament\Resources\IndustrySolutions\Pages;

use App\Filament\Resources\IndustrySolutions\IndustrySolutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIndustrySolutions extends ManageRecords
{
    protected static string $resource = IndustrySolutionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
