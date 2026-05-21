<?php

namespace App\Filament\Resources\ShopifyStores\Pages;

use App\Filament\Resources\ShopifyStores\ShopifyStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopifyStores extends ListRecords
{
    protected static string $resource = ShopifyStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
