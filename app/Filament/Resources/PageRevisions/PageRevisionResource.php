<?php

namespace App\Filament\Resources\PageRevisions;

use App\Enums\RevisionKind;
use App\Filament\Resources\PageRevisions\Pages\ListPageRevisions;
use App\Filament\Resources\Pages\PageResource;
use App\Models\PageRevision;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Daftar seluruh revisi konten lintas halaman (restore dilakukan dari halaman terkait). */
class PageRevisionResource extends Resource
{
    protected static ?string $model = PageRevision::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Revisi';

    protected static ?string $pluralModelLabel = 'Content Revisions';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['page' => fn ($query) => $query->withTrashed(), 'author']))
            ->columns([
                TextColumn::make('page.title')->label('Halaman')->searchable(),
                TextColumn::make('version')->label('#'),
                TextColumn::make('kind')->label('Jenis')->badge()->formatStateUsing(fn (RevisionKind $state) => $state->label()),
                TextColumn::make('note')->label('Catatan')->limit(40),
                TextColumn::make('author.name')->label('Oleh'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kind')->label('Jenis')->options(RevisionKind::options()),
                SelectFilter::make('page_id')->label('Halaman')->relationship('page', 'title'),
            ])
            ->recordActions([
                Action::make('preview')->label('Preview')->icon('heroicon-o-eye')
                    ->url(fn (PageRevision $r) => $r->page && ! $r->page->trashed() ? route('pages.preview', ['page' => $r->page, 'revision' => $r->id]) : null, shouldOpenInNewTab: true),
                Action::make('open')->label('Kelola & restore')->icon('heroicon-o-arrow-right')
                    ->url(fn (PageRevision $r) => $r->page && ! $r->page->trashed() ? PageResource::getUrl('edit', ['record' => $r->page]) : null),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPageRevisions::route('/')];
    }
}
