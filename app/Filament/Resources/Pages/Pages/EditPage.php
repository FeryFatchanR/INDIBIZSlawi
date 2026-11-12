<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Support\Access;
use App\Support\MediaUsageTracker;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('builder')->label('Buka Builder')->icon('heroicon-o-paint-brush')
                ->url(fn (Page $record) => PageResource::getUrl('build', ['record' => $record]))
                ->visible(fn (Page $record) => Access::can('build', $record)),
            PageResource::previewAction(),
            PageResource::publishAction(),
            PageResource::unpublishAction(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        MediaUsageTracker::sync($this->record, ['og_image_id' => $this->record->og_image_id], 'meta');
    }
}
