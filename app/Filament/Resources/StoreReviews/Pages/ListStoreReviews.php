<?php

namespace App\Filament\Resources\StoreReviews\Pages;

use App\Filament\Resources\StoreReviews\StoreReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreReviews extends ListRecords
{
    protected static string $resource = StoreReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
