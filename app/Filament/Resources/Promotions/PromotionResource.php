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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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
            \Filament\Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(150),
            \Filament\Forms\Components\TextInput::make('badge')->label('Badge (mis. Promo)')->maxLength(40),
            \Filament\Forms\Components\Textarea::make('description')->label('Deskripsi')->maxLength(2000)->rows(3)->columnSpanFull(),
            FormFields::media('image_id', 'Gambar'),
            \Filament\Forms\Components\Select::make('product_id')->label('Produk')->relationship('product', 'name')->searchable()->preload(),
            \Filament\Forms\Components\Select::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name')->searchable()->preload(),
            \Filament\Forms\Components\DateTimePicker::make('starts_at')->label('Mulai tayang'),
            \Filament\Forms\Components\DateTimePicker::make('ends_at')->label('Selesai tayang')->afterOrEqual('starts_at'),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($q) => $q->with('product'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')->label('Judul')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('product.name')->label('Produk'),
                \Filament\Tables\Columns\TextColumn::make('ends_at')->label('Selesai')->date(),
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
            'index' => ManagePromotions::route('/'),
        ];
    }
}
