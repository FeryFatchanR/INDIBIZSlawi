<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Support\Access;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /** Setelah membuat halaman, langsung buka visual builder. */
    protected function getRedirectUrl(): string
    {
        return Access::can('build', $this->record)
            ? PageResource::getUrl('build', ['record' => $this->record])
            : PageResource::getUrl('edit', ['record' => $this->record]);
    }
}
