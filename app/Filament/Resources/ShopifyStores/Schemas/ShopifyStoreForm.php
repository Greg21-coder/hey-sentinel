<?php

namespace App\Filament\Resources\ShopifyStores\Schemas;

use App\Enums\ScrapingStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShopifyStoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')
                    ->required(),
                TextInput::make('store_name'),
                TextInput::make('country_code'),
                TextInput::make('language_code'),
                TextInput::make('platform_tier'),
                TextInput::make('estimated_monthly_visits')
                    ->numeric(),
                TextInput::make('estimated_monthly_sales_usd')
                    ->numeric(),
                TextInput::make('employees_estimate')
                    ->numeric(),
                TextInput::make('theme_name'),
                TextInput::make('apps_installed_count')
                    ->numeric(),
                TextInput::make('storeleads_id'),
                TextInput::make('storeleads_payload_hash'),
                DateTimePicker::make('storeleads_synced_at'),
                Select::make('scraping_status')
                    ->options(ScrapingStatus::class)
                    ->default('pending')
                    ->required(),
            ]);
    }
}
