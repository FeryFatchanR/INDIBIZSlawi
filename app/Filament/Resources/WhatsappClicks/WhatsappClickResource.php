<?php

namespace App\Filament\Resources\WhatsappClicks;

use App\Filament\Resources\WhatsappClicks\Pages\ListWhatsappClicks;
use App\Models\WhatsappClick;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class WhatsappClickResource extends Resource
{
    protected static ?string $model = WhatsappClick::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Klik WhatsApp';

    protected static ?string $pluralModelLabel = 'WhatsApp Campaign Sources';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['campaign', 'product']))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('campaign.name')->label('Kampanye')->placeholder('-'),
                TextColumn::make('product.name')->label('Produk')->placeholder('-'),
                TextColumn::make('source_path')->label('Halaman asal')->placeholder('-')->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name'),
                SelectFilter::make('product_id')->label('Produk')->relationship('product', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWhatsappClicks::route('/')];
    }
}
