<?php

namespace App\Filament\Resources\StoreReviews\Pages;

use App\Filament\Resources\StoreReviews\StoreReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreReview extends EditRecord
{
    protected static string $resource = StoreReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
