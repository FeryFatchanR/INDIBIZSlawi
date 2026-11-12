<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\ManageCampaigns;
use App\Filament\Support\FormFields;
use App\Models\Campaign;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kampanye';

    protected static ?string $pluralModelLabel = 'Campaigns';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            FormFields::titleWithSlug('name', 'Nama kampanye'),
            FormFields::slug('campaigns'),
            \Filament\Forms\Components\TextInput::make('utm_source')->label('utm_source')->maxLength(100)->alphaDash(),
            \Filament\Forms\Components\TextInput::make('utm_medium')->label('utm_medium')->maxLength(100)->alphaDash(),
            \Filament\Forms\Components\TextInput::make('utm_campaign')->label('utm_campaign')->maxLength(100)->alphaDash()->helperText('Lead dengan utm_campaign ini otomatis terhubung ke kampanye.'),
            \Filament\Forms\Components\Textarea::make('whatsapp_message')->label('Pesan WhatsApp otomatis')->maxLength(500)->rows(2)->helperText('Dipakai tombol WhatsApp dengan parameter ?campaign=slug.'),
            \Filament\Forms\Components\Textarea::make('description')->label('Catatan')->maxLength(2000)->rows(2)->columnSpanFull(),
            \Filament\Forms\Components\DateTimePicker::make('starts_at')->label('Mulai tayang'),
            \Filament\Forms\Components\DateTimePicker::make('ends_at')->label('Selesai tayang')->afterOrEqual('starts_at'),
            \Filament\Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('utm_campaign')->label('utm_campaign')->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('leads_count')->label('Lead')->counts('leads')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('whatsapp_clicks_count')->label('Klik WA')->counts('whatsappClicks')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('starts_at')->label('Mulai')->date()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('ends_at')->label('Selesai')->date()->sortable(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => ManageCampaigns::route('/'),
        ];
    }
}
