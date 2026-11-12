<?php

namespace App\Filament\Resources\Statistics;

use App\Filament\Resources\Statistics\Pages\ManageStatistics;
use App\Filament\Support\FormFields;
use App\Models\Statistic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class StatisticResource extends Resource
{
    protected static ?string $model = Statistic::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Statistik';

    protected static ?string $pluralModelLabel = 'Statistics';

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            \Filament\Forms\Components\TextInput::make('value')->label('Nilai')->required()->maxLength(40),
            \Filament\Forms\Components\TextInput::make('suffix')->label('Akhiran (+, %, dll.)')->maxLength(20),
            \Filament\Forms\Components\TextInput::make('label')->label('Label')->required()->maxLength(120),
            \Filament\Forms\Components\Select::make('icon')->label('Ikon')->options(\App\Builder\Style\Icons::OPTIONS)->searchable(),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('value')->label('Nilai')->formatStateUsing(fn ($record) => $record->value.($record->suffix ?? '')),
                \Filament\Tables\Columns\TextColumn::make('label')->label('Label')->searchable(),
                \Filament\Tables\Columns\ToggleColumn::make('is_visible')->label('Tampil'),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_visible')->label('Tampil'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStatistics::route('/'),
        ];
    }
}
