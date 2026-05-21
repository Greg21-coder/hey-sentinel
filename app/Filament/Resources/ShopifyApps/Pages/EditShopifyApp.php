<?php

namespace App\Filament\Resources\ShopifyApps\Pages;

use App\Filament\Resources\ShopifyApps\ShopifyAppResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopifyApp extends EditRecord
{
    protected static string $resource = ShopifyAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
