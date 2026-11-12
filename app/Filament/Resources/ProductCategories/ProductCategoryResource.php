<?php

namespace App\Filament\Resources\ProductCategories;

use App\Filament\Resources\ProductCategories\Pages\ManageProductCategories;
use App\Filament\Support\FormFields;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'Produk';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kategori Produk';

    protected static ?string $pluralModelLabel = 'Kategori Produk';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            FormFields::titleWithSlug('name', 'Nama kategori', 120),
            FormFields::slug('product_categories', 140),
            \Filament\Forms\Components\Textarea::make('description')->label('Deskripsi')->maxLength(1000)->rows(2)->columnSpanFull(),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('products_count')->label('Produk')->counts('products'),
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
            'index' => ManageProductCategories::route('/'),
        ];
    }
}
