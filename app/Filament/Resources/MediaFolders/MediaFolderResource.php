<?php

namespace App\Filament\Resources\MediaFolders;

use App\Filament\Resources\MediaFolders\Pages\ManageMediaFolders;
use App\Filament\Support\FormFields;
use App\Models\MediaFolder;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MediaFolderResource extends Resource
{
    protected static ?string $model = MediaFolder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Folder Media';

    protected static ?string $pluralModelLabel = 'Folder Media';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            \Filament\Forms\Components\TextInput::make('name')->label('Nama folder')->required()->maxLength(120),
            \Filament\Forms\Components\Select::make('parent_id')->label('Folder induk')->relationship('parent', 'name')->searchable()->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($q) => $q->with('parent'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('parent.name')->label('Induk'),
                \Filament\Tables\Columns\TextColumn::make('assets_count')->label('Jumlah media')->counts('assets'),
            ])
            ->filters([

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
            'index' => ManageMediaFolders::route('/'),
        ];
    }
}
