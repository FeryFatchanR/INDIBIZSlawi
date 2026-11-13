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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
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
            TextInput::make('title')->label('Judul')->required()->maxLength(150),
            TextInput::make('subtitle')->label('Subjudul')->maxLength(255),
            FormFields::media('image_id', 'Gambar'),
            TextInput::make('placement')->label('Placement')->default('home')->required()->maxLength(40)->alphaDash()->helperText('Dipakai section Banner (sumber: menu Banners).'),
            TextInput::make('button_label')->label('Label tombol')->maxLength(80),
            FormFields::url('button_url', 'URL tombol'),
            Select::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name')->searchable()->preload(),
            DateTimePicker::make('starts_at')->label('Mulai tayang'),
            DateTimePicker::make('ends_at')->label('Selesai tayang')->afterOrEqual('starts_at'),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('campaign'))
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable(),
                TextColumn::make('placement')->label('Placement')->badge(),
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
            'index' => ManageBanners::route('/'),
        ];
    }
}
