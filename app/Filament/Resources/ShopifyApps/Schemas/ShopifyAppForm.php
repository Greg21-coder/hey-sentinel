<?php

namespace App\Filament\Resources\ShopifyApps\Schemas;

use App\Enums\ScrapingStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShopifyAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shopify_app_handle')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('developer_name')
                    ->required(),
                TextInput::make('developer_url')
                    ->url(),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('pricing_raw')
                    ->columnSpanFull(),
                TextInput::make('pricing_structured'),
                TextInput::make('pricing_min_usd')
                    ->numeric(),
                Toggle::make('pricing_has_free')
                    ->required(),
                TextInput::make('avatar_url')
                    ->url(),
                TextInput::make('average_rating')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_reviews')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_installs_estimate')
                    ->numeric(),
                Select::make('scraping_status')
                    ->options(ScrapingStatus::class)
                    ->default('pending')
                    ->required(),
                Textarea::make('scraping_error')
                    ->columnSpanFull(),
                DateTimePicker::make('last_scraped_at'),
                DateTimePicker::make('ai_processed_at'),
            ]);
    }
}
