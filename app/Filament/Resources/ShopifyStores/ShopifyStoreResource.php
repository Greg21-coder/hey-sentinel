<?php

namespace App\Filament\Resources\ShopifyStores;

use App\Filament\Resources\ShopifyStores\Pages\CreateShopifyStore;
use App\Filament\Resources\ShopifyStores\Pages\EditShopifyStore;
use App\Filament\Resources\ShopifyStores\Pages\ListShopifyStores;
use App\Filament\Resources\ShopifyStores\Schemas\ShopifyStoreForm;
use App\Filament\Resources\ShopifyStores\Tables\ShopifyStoresTable;
use App\Models\ShopifyStore;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShopifyStoreResource extends Resource
{
    protected static ?string $model = ShopifyStore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ShopifyStoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShopifyStoresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShopifyStores::route('/'),
            'create' => CreateShopifyStore::route('/create'),
            'edit' => EditShopifyStore::route('/{record}/edit'),
        ];
    }
}
