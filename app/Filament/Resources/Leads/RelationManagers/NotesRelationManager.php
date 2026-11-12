<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Actions\Crm\AddLeadNote;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Catatan';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('body')->label('Catatan')->wrap(),
                TextColumn::make('user.name')->label('Oleh')->placeholder('Sistem'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                Action::make('add')->label('Tambah catatan')->icon('heroicon-o-plus')
                    ->authorize(fn () => auth()->user()->can('addNote', $this->getOwnerRecord()))
                    ->schema([Textarea::make('body')->label('Catatan')->required()->maxLength(5000)->rows(4)])
                    ->action(fn (array $data) => app(AddLeadNote::class)->execute($this->getOwnerRecord(), auth()->user(), $data['body'])),
            ]);
    }
}
