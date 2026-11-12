<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Support\FormFields;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            \Filament\Forms\Components\TextInput::make('question')->label('Pertanyaan')->required()->maxLength(255)->columnSpanFull(),
            \Filament\Forms\Components\Textarea::make('answer')->label('Jawaban')->required()->maxLength(3000)->rows(4)->columnSpanFull(),
            \Filament\Forms\Components\TextInput::make('group')->label('Grup')->default('general')->required()->maxLength(60)->alphaDash()->helperText('Dipakai section FAQ untuk memilih kumpulan pertanyaan.'),
            FormFields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('question')->label('Pertanyaan')->searchable()->limit(70),
                \Filament\Tables\Columns\TextColumn::make('group')->label('Grup')->badge(),
                \Filament\Tables\Columns\ToggleColumn::make('is_visible')->label('Tampil'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('group')->label('Grup')->options(fn () => \App\Models\Faq::query()->distinct()->pluck('group', 'group')),
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
            'index' => ManageFaqs::route('/'),
        ];
    }
}
