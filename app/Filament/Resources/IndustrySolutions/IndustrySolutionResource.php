<?php

namespace App\Filament\Resources\IndustrySolutions;

use App\Builder\Style\Icons;
use App\Filament\Resources\IndustrySolutions\Pages\ManageIndustrySolutions;
use App\Filament\Support\FormFields;
use App\Models\IndustrySolution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class IndustrySolutionResource extends Resource
{
    protected static ?string $model = IndustrySolution::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Solusi Industri';

    protected static ?string $pluralModelLabel = 'Industry Solutions';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            FormFields::titleWithSlug('name', 'Nama industri', 120),
            FormFields::slug('industry_solutions', 140),
            Select::make('icon')->label('Ikon')->options(Icons::OPTIONS)->searchable(),
            FormFields::media('image_id', 'Gambar'),
            Textarea::make('short_description')->label('Deskripsi singkat')->maxLength(500)->rows(2)->columnSpanFull(),
            RichEditor::make('description')->label('Deskripsi lengkap')->columnSpanFull()->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
            Select::make('products')->label('Produk yang direkomendasikan')->relationship('products', 'name')->multiple()->preload()->columnSpanFull(),
            ...FormFields::seoFields(),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('products_count')->label('Produk')->counts('products'),
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
            'index' => ManageIndustrySolutions::route('/'),
        ];
    }
}
