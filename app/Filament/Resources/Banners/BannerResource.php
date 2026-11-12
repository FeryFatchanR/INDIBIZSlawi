<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Filament\Support\FormFields;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Banner';

    protected static ?string $pluralModelLabel = 'Banners';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            \Filament\Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(150),
            \Filament\Forms\Components\TextInput::make('subtitle')->label('Subjudul')->maxLength(255),
            FormFields::media('image_id', 'Gambar'),
            \Filament\Forms\Components\TextInput::make('placement')->label('Placement')->default('home')->required()->maxLength(40)->alphaDash()->helperText('Dipakai section Banner (sumber: menu Banners).'),
            \Filament\Forms\Components\TextInput::make('button_label')->label('Label tombol')->maxLength(80),
            FormFields::url('button_url', 'URL tombol'),
            \Filament\Forms\Components\Select::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name')->searchable()->preload(),
            \Filament\Forms\Components\DateTimePicker::make('starts_at')->label('Mulai tayang'),
            \Filament\Forms\Components\DateTimePicker::make('ends_at')->label('Selesai tayang')->afterOrEqual('starts_at'),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('campaign'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')->label('Judul')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('placement')->label('Placement')->badge(),
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
            'index' => ManageBanners::route('/'),
        ];
    }
}
