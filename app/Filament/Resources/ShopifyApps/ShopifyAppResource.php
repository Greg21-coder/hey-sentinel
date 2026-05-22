<?php

namespace App\Filament\Resources\ShopifyApps;

use App\Filament\Resources\ShopifyApps\Pages\ListShopifyApps;
use App\Filament\Resources\ShopifyApps\Pages\ViewShopifyApp;
use App\Filament\Resources\ShopifyApps\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\ShopifyApps\Tables\ShopifyAppsTable;
use App\Models\ShopifyApp;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShopifyAppResource extends Resource
{
    protected static ?string $model = ShopifyApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Datos';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Name'),
            TextEntry::make('shopify_app_handle')->label('Handle'),
            TextEntry::make('developer_name')->label('Developer'),
            TextEntry::make('developer_url')->label('Developer URL')->url(fn ($state) => $state, true),
            TextEntry::make('category.name')->label('Category')->badge(),
            TextEntry::make('description')->label('Description')->columnSpanFull()->markdown(),
            TextEntry::make('pricing_min_usd')->label('Pricing min (USD)')->money('USD'),
            TextEntry::make('pricing_has_free')->label('Has free plan')->badge(),
            TextEntry::make('average_rating')->label('Average rating')->numeric(decimalPlaces: 2),
            TextEntry::make('total_reviews')->label('Total reviews')->numeric(),
            TextEntry::make('total_installs_estimate')->label('Installs estimate')->numeric()->placeholder('—'),
            TextEntry::make('scraping_status')->label('Scraping status')->badge(),
            TextEntry::make('last_scraped_at')->label('Last scraped')->since()->placeholder('Never'),
            TextEntry::make('ai_processed_at')->label('AI processed')->since()->placeholder('Never'),
        ])->columns(2);
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
            'view' => ViewShopifyApp::route('/{record}'),
        ];
    }
}
