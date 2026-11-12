<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Filament\Support\FormFields;
use App\Models\Partner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Mitra';

    protected static ?string $pluralModelLabel = 'Partners';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            \Filament\Forms\Components\TextInput::make('name')->label('Nama mitra / klien')->required()->maxLength(150),
            FormFields::url('url', 'Website (opsional)'),
            FormFields::media('logo_id', 'Logo'),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('logo'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('url')->label('Website')->limit(40),
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
            'index' => ManagePartners::route('/'),
        ];
    }
}
