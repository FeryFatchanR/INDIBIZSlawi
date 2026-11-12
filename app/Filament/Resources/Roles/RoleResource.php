<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles & Permissions';

    public static function form(Schema $schema): Schema
    {
        $groups = Permission::query()->orderBy('name')->pluck('name')
            ->groupBy(fn ($name) => explode('.', $name)[0]);

        return $schema->components([
            TextInput::make('name')->label('Nama role')->required()->maxLength(60)->alphaDash()
                ->unique('roles', 'name', ignoreRecord: true)
                ->disabled(fn (?Role $record) => $record && in_array($record->name, Roles::PANEL_ROLES, true))
                ->dehydrated(fn (?Role $record) => ! $record || ! in_array($record->name, Roles::PANEL_ROLES, true)),
            Section::make('Permissions')->columns(3)->schema(
                $groups->map(fn ($names, $group) => CheckboxList::make("permission_groups.{$group}")
                    ->label(str_replace('_', ' ', ucfirst($group)))
                    ->options($names->mapWithKeys(fn ($n) => [$n => explode('.', $n, 2)[1]])->all())
                    ->bulkToggleable())->values()->all()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Role')->formatStateUsing(fn ($state) => Roles::label($state)),
                TextColumn::make('permissions_count')->label('Permissions')->counts('permissions'),
                TextColumn::make('users_count')->label('Pengguna')->counts('users'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
