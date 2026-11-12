<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Input lead manual')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'new' => Tab::make('New')->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::New)),
            'progress' => Tab::make('In progress')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', LeadStatus::inProgress())),
            'won' => Tab::make('Won')->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::Won)),
            'lost' => Tab::make('Lost')->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::Lost)),
        ];
    }
}
