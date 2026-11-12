<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Activity Logs';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('Waktu')->dateTime(),
            TextEntry::make('user.name')->label('Pengguna')->placeholder('Sistem'),
            TextEntry::make('event')->label('Event'),
            TextEntry::make('description')->label('Deskripsi'),
            TextEntry::make('subject_type')->label('Objek'),
            KeyValueEntry::make('properties')->label('Detail')->state(fn (ActivityLog $r) => collect($r->properties ?? [])->map(fn ($v) => is_array($v) ? json_encode($v) : (string) $v)->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem')->searchable(),
                TextColumn::make('event')->label('Event')->badge()->searchable(),
                TextColumn::make('description')->label('Deskripsi')->limit(60)->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')->options(fn () => ActivityLog::query()->distinct()->orderBy('event')->pluck('event', 'event')),
                SelectFilter::make('user_id')->label('Pengguna')->relationship('user', 'name'),
                Filter::make('date')->schema([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }
}
