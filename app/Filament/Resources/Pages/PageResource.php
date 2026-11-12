<?php

namespace App\Filament\Resources\Pages;

use App\Actions\Cms\PublishPage;
use App\Actions\Cms\UnpublishPage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Exceptions\StaleDraftException;
use App\Filament\Resources\Pages\Pages\BuildPage;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\RelationManagers\RevisionsRelationManager;
use App\Filament\Support\FormFields;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Halaman';

    protected static ?string $pluralModelLabel = 'Pages';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Halaman')->columns(2)->columnSpanFull()->schema([
                FormFields::titleWithSlug('title', 'Judul halaman', 200),
                FormFields::slug('pages', 160, config('cms.reserved_slugs'))
                    ->helperText('URL publik: /slug. Perubahan slug langsung mengubah URL halaman yang sudah tayang.'),
                Select::make('type')->label('Tipe')->required()->default(PageType::Page->value)
                    ->options(PageType::options())
                    ->rules(fn (?Page $record) => [
                        // Hanya boleh ada satu homepage.
                        Rule::unique('pages', 'type')->where('type', PageType::Home->value)->whereNull('deleted_at')->ignore($record?->id),
                    ])
                    ->validationMessages(['unique' => 'Homepage sudah ada. Hanya boleh satu halaman bertipe Homepage.'])
                    ->helperText('Global Section = blok yang bisa disisipkan ke banyak halaman, tidak punya URL sendiri.'),
            ]),
            Section::make('SEO')->columns(2)->columnSpanFull()->collapsible()->schema([
                ...FormFields::seoFields(),
                TextInput::make('canonical_url')->label('Canonical URL (opsional)')->url()->maxLength(255),
                FormFields::media('og_image_id', 'Gambar Open Graph'),
                Toggle::make('noindex')->label('Jangan indeks di mesin pencari (noindex)'),
            ])->description('Perubahan SEO tayang setelah halaman dipublikasikan ulang.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with(['editor', 'publishedRevision']))
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->sortable()
                    ->description(fn (Page $r) => $r->isHome() ? '/' : ($r->type === PageType::Global ? 'Global section' : '/'.$r->slug)),
                TextColumn::make('type')->label('Tipe')->badge()->formatStateUsing(fn (PageType $state) => $state->label()),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (PageStatus $state) => $state->label())
                    ->color(fn (PageStatus $state) => match ($state) { PageStatus::Published => 'success', PageStatus::Draft => 'gray', PageStatus::Unpublished => 'warning' }),
                IconColumn::make('pending')->label('Draft belum terbit')->boolean()
                    ->state(fn (Page $r) => $r->hasUnpublishedChanges())
                    ->trueIcon('heroicon-o-exclamation-circle')->trueColor('warning')->falseIcon('heroicon-o-check-circle')->falseColor('success'),
                TextColumn::make('published_at')->label('Terbit')->since()->sortable(),
                TextColumn::make('editor.name')->label('Diubah oleh')->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(PageStatus::options()),
                SelectFilter::make('type')->label('Tipe')->options(PageType::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('builder')->label('Builder')->icon('heroicon-o-paint-brush')->color('primary')
                    ->url(fn (Page $r) => self::getUrl('build', ['record' => $r]))
                    ->visible(fn (Page $r) => auth()->user()->can('build', $r)),
                ActionGroup::make([
                    self::previewAction(),
                    self::publishAction(),
                    self::unpublishAction(),
                    Action::make('view_public')->label('Lihat di website')->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Page $r) => $r->publicUrl(), shouldOpenInNewTab: true)
                        ->visible(fn (Page $r) => $r->isPublished() && $r->type !== PageType::Global),
                    EditAction::make()->label('Pengaturan'),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ]);
    }

    public static function previewAction(): Action
    {
        return Action::make('preview')->label('Preview draft')->icon('heroicon-o-eye')
            ->url(fn (Page $record) => route('pages.preview', $record), shouldOpenInNewTab: true)
            ->visible(fn (Page $record) => auth()->user()->can('preview', $record));
    }

    public static function publishAction(): Action
    {
        return Action::make('publish')->label('Publish')->icon('heroicon-o-rocket-launch')->color('success')
            ->requiresConfirmation()
            ->modalDescription('Draft saat ini akan menggantikan versi publik. Versi publik sebelumnya tetap tersimpan di riwayat revisi.')
            ->visible(fn (Page $record) => auth()->user()->can('publish', $record))
            ->action(function (Page $record) {
                try {
                    app(PublishPage::class)->execute($record, null, auth()->user());
                    Notification::make()->title('Halaman dipublikasikan.')->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()->title('Gagal publish: konfigurasi section tidak valid.')->body(collect($e->errors())->flatten()->first())->danger()->send();
                }
            });
    }

    public static function unpublishAction(): Action
    {
        return Action::make('unpublish')->label('Unpublish')->icon('heroicon-o-eye-slash')->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Page $record) => $record->isPublished() && ! $record->isHome() && auth()->user()->can('publish', $record))
            ->action(function (Page $record) {
                app(UnpublishPage::class)->execute($record, auth()->user());
                Notification::make()->title('Halaman tidak lagi tampil di website.')->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [RevisionsRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'build' => BuildPage::route('/{record}/builder'),
        ];
    }
}
