<?php

namespace App\Filament\Resources\Promotions;

use App\Filament\Resources\Promotions\Pages\ManagePromotions;
use App\Filament\Support\FormFields;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Promosi';

    protected static ?string $pluralModelLabel = 'Promotions';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('Judul')->required()->maxLength(150),
            TextInput::make('badge')->label('Badge (mis. Promo)')->maxLength(40),
            Textarea::make('description')->label('Deskripsi')->maxLength(2000)->rows(3)->columnSpanFull(),
            FormFields::media('image_id', 'Gambar'),
            Select::make('product_id')->label('Produk')->relationship('product', 'name')->searchable()->preload(),
            Select::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name')->searchable()->preload(),
            DateTimePicker::make('starts_at')->label('Mulai tayang'),
            DateTimePicker::make('ends_at')->label('Selesai tayang')->afterOrEqual('starts_at'),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('product'))
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable(),
                TextColumn::make('product.name')->label('Produk'),
                TextColumn::make('ends_at')->label('Selesai')->date(),
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
            'index' => ManagePromotions::route('/'),
        ];
    }
}
