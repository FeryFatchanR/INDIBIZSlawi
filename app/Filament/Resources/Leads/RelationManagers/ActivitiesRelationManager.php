<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Riwayat follow-up (read-only, append-only). */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Riwayat Follow-up';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                TextColumn::make('type')->label('Jenis')->badge(),
                TextColumn::make('description')->label('Keterangan')->wrap(),
                TextColumn::make('user.name')->label('Oleh')->placeholder('Sistem'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
