<?php

namespace App\Filament\Resources\ShopifyStores;

use App\Filament\Resources\ShopifyStores\Pages\ListShopifyStores;
use App\Filament\Resources\ShopifyStores\Tables\ShopifyStoresTable;
use App\Models\ShopifyStore;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShopifyStoreResource extends Resource
{
    protected static ?string $model = ShopifyStore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static \UnitEnum|string|null $navigationGroup = 'Datos';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return ShopifyStoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShopifyStores::route('/'),
        ];
    }
}
