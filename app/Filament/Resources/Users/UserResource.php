<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(150),
            TextInput::make('email')->label('Email')->email()->required()->maxLength(180)->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Telepon')->tel()->maxLength(30),
            TextInput::make('password')->label('Password')->password()->revealable()
                ->rule(Password::min(12)->letters()->numbers())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText('Minimal 12 karakter, kombinasi huruf & angka. Kosongkan saat edit bila tidak diubah.'),
            Toggle::make('is_active')->label('Akun aktif')->default(true)
                ->helperText('Akun nonaktif tidak dapat login ke admin panel.'),
            CheckboxList::make('role_names')->label('Role')->required()->columnSpanFull()
                ->options(fn () => self::assignableRoles())
                ->descriptions([
                    Roles::SUPER_ADMIN => 'Akses penuh termasuk user, role, dan pengaturan sistem.',
                    Roles::ADMINISTRATOR => 'Kelola halaman, builder, produk, media, leads.',
                    Roles::MARKETING => 'Konten marketing & lead yang di-assign.',
                    Roles::SALES => 'Hanya lead yang di-assign: status & catatan.',
                ]),
        ]);
    }

    /** Role yang boleh diberikan oleh user saat ini (non-super-admin tidak dapat memberi super_admin). */
    public static function assignableRoles(): array
    {
        return Role::query()->orderBy('id')->pluck('name', 'name')
            ->reject(fn ($name) => $name === Roles::SUPER_ADMIN && ! auth()->user()?->isSuperAdmin())
            ->map(fn ($name) => Roles::label($name))
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('roles.name')->label('Role')->badge()->formatStateUsing(fn ($state) => Roles::label($state)),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Dibuat')->date()->sortable(),
            ])
            ->filters([SelectFilter::make('roles')->label('Role')->relationship('roles', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
