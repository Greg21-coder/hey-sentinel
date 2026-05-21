<?php

namespace App\Filament\Resources\ShopifyStores\Pages;

use App\Filament\Resources\ShopifyStores\ShopifyStoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopifyStore extends EditRecord
{
    protected static string $resource = ShopifyStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
