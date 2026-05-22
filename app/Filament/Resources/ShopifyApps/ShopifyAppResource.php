<?php

namespace App\Filament\Resources\ShopifyApps;

use App\Filament\Resources\ShopifyApps\Pages\CreateShopifyApp;
use App\Filament\Resources\ShopifyApps\Pages\EditShopifyApp;
use App\Filament\Resources\ShopifyApps\Pages\ListShopifyApps;
use App\Filament\Resources\ShopifyApps\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\ShopifyApps\Schemas\ShopifyAppForm;
use App\Filament\Resources\ShopifyApps\Tables\ShopifyAppsTable;
use App\Models\ShopifyApp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShopifyAppResource extends Resource
{
    protected static ?string $model = ShopifyApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ShopifyAppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShopifyAppsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShopifyApps::route('/'),
            'create' => CreateShopifyApp::route('/create'),
            'edit' => EditShopifyApp::route('/{record}/edit'),
        ];
    }
}
