<?php

namespace App\Filament\Resources\CaseStudies;

use App\Filament\Resources\CaseStudies\Pages\ManageCaseStudies;
use App\Filament\Support\FormFields;
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
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

class CaseStudyResource extends Resource
{
    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Studi Kasus';

    protected static ?string $pluralModelLabel = 'Case Studies';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            FormFields::titleWithSlug('title', 'Judul', 180),
            FormFields::slug('case_studies', 190),
            TextInput::make('client_name')->label('Nama klien')->required()->maxLength(150),
            Select::make('industry_solution_id')->label('Industri')->relationship('industrySolution', 'name')->searchable()->preload(),
            Textarea::make('summary')->label('Ringkasan')->maxLength(500)->rows(2)->columnSpanFull(),
            RichEditor::make('challenge')->label('Tantangan')->columnSpanFull()->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
            RichEditor::make('solution')->label('Solusi')->columnSpanFull()->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
            RichEditor::make('result')->label('Hasil')->columnSpanFull()->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
            FormFields::media('image_id', 'Gambar utama'),
            ...FormFields::seoFields(),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('industrySolution'))
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->limit(50),
                TextColumn::make('client_name')->label('Klien')->searchable(),
                TextColumn::make('industrySolution.name')->label('Industri'),
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
            'index' => ManageCaseStudies::route('/'),
        ];
    }
}
