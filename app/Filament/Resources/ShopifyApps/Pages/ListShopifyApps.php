<?php

namespace App\Filament\Resources\ShopifyApps\Pages;

use App\Filament\Resources\ShopifyApps\ShopifyAppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopifyApps extends ListRecords
{
    protected static string $resource = ShopifyAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
