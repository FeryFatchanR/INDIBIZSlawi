<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\MediaAssets\Pages\ManageMediaAssets;
use App\Models\MediaAsset;
use App\Support\MediaUploader;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Media';

    protected static ?string $pluralModelLabel = 'Media Library';

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Judul')->maxLength(255),
            TextInput::make('alt')->label('Alt text')->maxLength(255)->helperText('Deskripsi gambar untuk aksesibilitas & SEO.'),
            Select::make('folder_id')->label('Folder')->relationship('folder', 'name')->searchable()->preload(),
        ]);
    }

    /** Field upload (disimpan sementara di disk privat lalu diproses MediaUploader). */
    public static function uploadField(): FileUpload
    {
        $mimes = [...config('cms.media.image_mimes'), ...config('cms.media.video_mimes'), ...config('cms.media.document_mimes')];

        return FileUpload::make('file')->label('File')->required()
            ->disk('local')->directory('media-tmp')->visibility('private')
            ->acceptedFileTypes($mimes)
            ->maxSize((int) config('cms.media.max_video_kb'))
            ->storeFileNamesIn('original_name')
            ->helperText('JPG/PNG/WebP/GIF maks. 5 MB, MP4/WebM maks. 50 MB, PDF maks. 10 MB. SVG tidak diizinkan.');
    }

    /** Proses file sementara → MediaAsset, lalu hapus file sementara. */
    public static function processUpload(array $data, ?MediaAsset $replace = null): MediaAsset
    {
        $tmp = (string) $data['file'];
        try {
            return app(MediaUploader::class)->store(
                Storage::disk('local')->path($tmp),
                (string) ($data['original_name'] ?? basename($tmp)),
                isset($data['folder_id']) ? (int) $data['folder_id'] : null,
                auth()->user(),
                $replace,
            );
        } finally {
            Storage::disk('local')->delete($tmp);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with('folder')->withCount('usages'))
            ->columns([
                ImageColumn::make('preview')->label('')->state(fn (MediaAsset $r) => $r->isImage() ? $r->url(480) : null)
                    ->imageHeight(48),
                TextColumn::make('original_name')->label('Nama file')->searchable(['original_name', 'title', 'alt'])->limit(40)
                    ->description(fn (MediaAsset $r) => $r->title),
                TextColumn::make('kind')->label('Jenis')->badge(),
                TextColumn::make('folder.name')->label('Folder'),
                TextColumn::make('size')->label('Ukuran')->formatStateUsing(fn ($state) => number_format($state / 1024, 0, ',', '.').' KB')->sortable(),
                TextColumn::make('dimensions')->label('Dimensi')->state(fn (MediaAsset $r) => $r->width ? "{$r->width}×{$r->height}" : '-'),
                TextColumn::make('usages_count')->label('Dipakai')->badge()->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('Diunggah')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kind')->label('Jenis')->options(['image' => 'Gambar', 'video' => 'Video', 'document' => 'Dokumen']),
                SelectFilter::make('folder_id')->label('Folder')->relationship('folder', 'name'),
                Filter::make('unused')->label('Tidak dipakai')->query(fn (Builder $q) => $q->doesntHave('usages')),
            ])
            ->recordActions([
                Action::make('open')->label('Buka')->icon('heroicon-o-eye')->url(fn (MediaAsset $r) => $r->url(), shouldOpenInNewTab: true),
                Action::make('replace')->label('Ganti file')->icon('heroicon-o-arrow-path')
                    ->authorize(fn (MediaAsset $record) => auth()->user()->can('update', $record))
                    ->schema([self::uploadField()])
                    ->action(function (MediaAsset $record, array $data) {
                        self::processUpload($data, $record);
                        Notification::make()->title('File diganti. Semua konten yang memakai media ini ikut terbarui.')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (MediaAsset $record, DeleteAction $action) {
                        if ($record->isInUse()) {
                            Notification::make()->title('Media masih dipakai konten dan tidak dapat dihapus.')->danger()->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('delete_unused')->label('Hapus yang tidak dipakai')->icon('heroicon-o-trash')->color('danger')
                    ->requiresConfirmation()
                    ->authorize(fn () => auth()->user()->can('media.delete'))
                    ->action(function (Collection $records) {
                        $deleted = 0;
                        foreach ($records as $record) {
                            if (! $record->isInUse()) {
                                $record->delete();
                                $deleted++;
                            }
                        }
                        Notification::make()->title("{$deleted} media dihapus; media yang masih dipakai dilewati.")->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMediaAssets::route('/')];
    }
}
