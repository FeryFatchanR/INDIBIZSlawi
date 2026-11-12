<?php

namespace App\Filament\Resources\Pages\RelationManagers;

use App\Actions\Cms\RestoreRevision;
use App\Enums\RevisionKind;
use App\Exceptions\StaleDraftException;
use App\Models\PageRevision;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Riwayat Revisi';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $page = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn ($q) => $q->with('author'))
            ->columns([
                TextColumn::make('version')->label('#')->sortable(),
                TextColumn::make('kind')->label('Jenis')->badge()
                    ->formatStateUsing(fn (RevisionKind $state) => $state->label())
                    ->color(fn (RevisionKind $state) => match ($state) { RevisionKind::Publish => 'success', RevisionKind::Restore => 'warning', default => 'gray' }),
                TextColumn::make('live')->label('')->state(fn (PageRevision $r) => $r->id === $page->published_revision_id ? 'Sedang tayang' : null)->badge()->color('success'),
                TextColumn::make('note')->label('Catatan')->limit(50),
                TextColumn::make('author.name')->label('Oleh'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('version', 'desc')
            ->filters([SelectFilter::make('kind')->label('Jenis')->options(RevisionKind::options())])
            ->recordActions([
                Action::make('preview')->label('Preview')->icon('heroicon-o-eye')
                    ->url(fn (PageRevision $r) => route('pages.preview', ['page' => $page, 'revision' => $r->id]), shouldOpenInNewTab: true),
                Action::make('restore')->label('Pulihkan ke draft')->icon('heroicon-o-arrow-uturn-left')->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Isi revision ini akan menggantikan DRAFT. Versi publik tidak berubah sampai Anda publish ulang.')
                    ->visible(fn () => auth()->user()->can('restoreRevision', $page))
                    ->action(function (PageRevision $record) use ($page) {
                        try {
                            app(RestoreRevision::class)->execute($page, $record, null, auth()->user());
                            Notification::make()->title("Revision #{$record->version} dipulihkan ke draft.")->success()->send();
                        } catch (ValidationException|StaleDraftException $e) {
                            Notification::make()->title('Gagal memulihkan revision.')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
