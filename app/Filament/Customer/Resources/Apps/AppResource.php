<?php

namespace App\Filament\Customer\Resources\Apps;

use App\Filament\Customer\Resources\Apps\Pages\ListApps;
use App\Filament\Customer\Resources\Apps\Tables\AppsTable;
use App\Models\ShopifyApp;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppResource extends Resource
{
    protected static ?string $model = ShopifyApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Browse Apps';

    protected static ?string $modelLabel = 'App';

    protected static ?string $pluralModelLabel = 'Apps';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Name'),
            TextEntry::make('developer_name')->label('Developer'),
            TextEntry::make('category.name')->label('Category')->badge(),
            TextEntry::make('description')->label('Description')->columnSpanFull()->markdown(),
            TextEntry::make('average_rating')->label('Average rating')->numeric(decimalPlaces: 2),
            TextEntry::make('total_reviews')->label('Total reviews')->numeric(),
            TextEntry::make('pricing_min_usd')->label('Pricing min (USD)')->money('USD'),
            TextEntry::make('pricing_has_free')->label('Has free plan')->badge(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return AppsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApps::route('/'),
        ];
    }
}
