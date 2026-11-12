<?php

namespace App\Filament\Resources\PageRevisions\Pages;

use App\Filament\Resources\PageRevisions\PageRevisionResource;
use Filament\Resources\Pages\ListRecords;

class ListPageRevisions extends ListRecords
{
    protected static string $resource = PageRevisionResource::class;
}
