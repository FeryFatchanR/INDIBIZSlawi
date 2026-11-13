<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\MediaAsset;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageMediaAssets extends ManageRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')->label('Upload media')->icon('heroicon-o-arrow-up-tray')
                ->authorize(fn () => Access::can('media.create'))
                ->schema([
                    MediaAssetResource::uploadField(),
                    Select::make('folder_id')->label('Folder')->relationship('folder', 'name', fn ($query) => $query)->searchable()->preload()
                        ->model(MediaAsset::class),
                    TextInput::make('alt')->label('Alt text')->maxLength(255),
                ])
                ->action(function (array $data) {
                    $asset = MediaAssetResource::processUpload($data);
                    if (filled($data['alt'] ?? null)) {
                        $asset->update(['alt' => $data['alt']]);
                    }
                    Notification::make()->title('Media berhasil diunggah.')->success()->send();
                }),
        ];
    }
}
