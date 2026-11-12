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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
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
            TextInput::make('utm_source')->label('utm_source')->maxLength(100)->alphaDash(),
            TextInput::make('utm_medium')->label('utm_medium')->maxLength(100)->alphaDash(),
            TextInput::make('utm_campaign')->label('utm_campaign')->maxLength(100)->alphaDash()->helperText('Lead dengan utm_campaign ini otomatis terhubung ke kampanye.'),
            Textarea::make('whatsapp_message')->label('Pesan WhatsApp otomatis')->maxLength(500)->rows(2)->helperText('Dipakai tombol WhatsApp dengan parameter ?campaign=slug.'),
            Textarea::make('description')->label('Catatan')->maxLength(2000)->rows(2)->columnSpanFull(),
            DateTimePicker::make('starts_at')->label('Mulai tayang'),
            DateTimePicker::make('ends_at')->label('Selesai tayang')->afterOrEqual('starts_at'),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('utm_campaign')->label('utm_campaign')->toggleable(),
                TextColumn::make('leads_count')->label('Lead')->counts('leads')->sortable(),
                TextColumn::make('whatsapp_clicks_count')->label('Klik WA')->counts('whatsappClicks')->sortable(),
                TextColumn::make('starts_at')->label('Mulai')->date()->sortable(),
                TextColumn::make('ends_at')->label('Selesai')->date()->sortable(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
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
