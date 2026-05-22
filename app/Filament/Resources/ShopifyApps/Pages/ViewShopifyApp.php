<?php

namespace App\Filament\Resources\ShopifyApps\Pages;

use App\Filament\Resources\ShopifyApps\ShopifyAppResource;
use Filament\Resources\Pages\ViewRecord;

class ViewShopifyApp extends ViewRecord
{
    protected static string $resource = ShopifyAppResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Detalles';
    }
}
