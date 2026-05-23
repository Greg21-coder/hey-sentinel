<?php

namespace App\Filament\Customer\Resources\Apps\Pages;

use App\Filament\Customer\Resources\Apps\AppResource;
use Filament\Resources\Pages\ListRecords;

class ListApps extends ListRecords
{
    protected static string $resource = AppResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
