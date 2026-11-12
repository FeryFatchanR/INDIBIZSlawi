<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\FormFields;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleries';

    protected static ?string $title = 'Galeri';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            FormFields::media('media_asset_id', 'Gambar')->required(),
            TextInput::make('caption')->label('Keterangan')->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        $sync = fn () => ProductResource::syncMediaUsage($this->getOwnerRecord());

        return $table
            ->modifyQueryUsing(fn ($q) => $q->with('media'))
            ->columns([
                ImageColumn::make('thumbnail')->label('Gambar')->state(fn ($record) => $record->media?->url(480)),
                TextColumn::make('caption')->label('Keterangan'),
            ])
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->headerActions([CreateAction::make()->after($sync)])
            ->recordActions([EditAction::make()->after($sync), DeleteAction::make()->after($sync)]);
    }
}
