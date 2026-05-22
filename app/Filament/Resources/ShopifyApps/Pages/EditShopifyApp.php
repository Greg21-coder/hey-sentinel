<?php

namespace App\Filament\Resources\ShopifyApps\Pages;

use App\Filament\Resources\ShopifyApps\ShopifyAppResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopifyApp extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
