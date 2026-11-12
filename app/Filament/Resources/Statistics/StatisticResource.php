<?php

namespace App\Filament\Resources\Statistics;

use App\Builder\Style\Icons;
use App\Filament\Resources\Statistics\Pages\ManageStatistics;
use App\Filament\Support\FormFields;
use App\Models\Statistic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
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
            TextInput::make('value')->label('Nilai')->required()->maxLength(40),
            TextInput::make('suffix')->label('Akhiran (+, %, dll.)')->maxLength(20),
            TextInput::make('label')->label('Label')->required()->maxLength(120),
            Select::make('icon')->label('Ikon')->options(Icons::OPTIONS)->searchable(),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')->label('Nilai')->formatStateUsing(fn ($record) => $record->value.($record->suffix ?? '')),
                TextColumn::make('label')->label('Label')->searchable(),
                ToggleColumn::make('is_visible')->label('Tampil'),
            ])
            ->filters([
                TernaryFilter::make('is_visible')->label('Tampil'),
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
