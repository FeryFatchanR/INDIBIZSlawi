<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpecificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'specifications';

    protected static ?string $title = 'Spesifikasi';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Label')->required()->maxLength(120),
            TextInput::make('value')->label('Nilai')->required()->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('label')->label('Label'), TextColumn::make('value')->label('Nilai')])
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
