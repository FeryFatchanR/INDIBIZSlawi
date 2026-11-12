<?php

namespace App\Filament\Resources\NavigationMenus;

use App\Filament\Resources\NavigationMenus\Pages\EditNavigationMenu;
use App\Filament\Resources\NavigationMenus\Pages\ListNavigationMenus;
use App\Filament\Resources\NavigationMenus\RelationManagers\ItemsRelationManager;
use App\Models\NavigationMenu;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NavigationMenuResource extends Resource
{
    protected static ?string $model = NavigationMenu::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Menu Navigasi';

    protected static ?string $pluralModelLabel = 'Navigation Menus';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama menu')->required()->maxLength(120),
            TextInput::make('key')->label('Key')->required()->maxLength(60)->alphaDash()->unique(ignoreRecord: true)
                ->helperText('Dipakai template: header, footer_products, footer_company.')
                ->disabled(fn (?NavigationMenu $record) => $record !== null && in_array($record->key, ['header', 'footer_products', 'footer_company'], true))
                ->dehydrated(fn (?NavigationMenu $record) => $record === null || ! in_array($record->key, ['header', 'footer_products', 'footer_company'], true)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama'),
                TextColumn::make('key')->label('Key')->badge(),
                TextColumn::make('items_count')->label('Item')->counts('items'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationMenus::route('/'),
            'edit' => EditNavigationMenu::route('/{record}/edit'),
        ];
    }
}
