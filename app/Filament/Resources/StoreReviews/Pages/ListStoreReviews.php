<?php

namespace App\Filament\Resources\StoreReviews\Pages;

use App\Filament\Resources\StoreReviews\StoreReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListStoreReviews extends ListRecords
{
    protected static string $resource = StoreReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
