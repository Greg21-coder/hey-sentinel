<?php

namespace App\Filament\Resources\ShopifyStores\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShopifyStoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->searchable(),
                TextColumn::make('store_name')
                    ->searchable(),
                TextColumn::make('country_code')
                    ->searchable(),
                TextColumn::make('language_code')
                    ->searchable(),
                TextColumn::make('platform_tier')
                    ->searchable(),
                TextColumn::make('estimated_monthly_visits')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_monthly_sales_usd')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employees_estimate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('theme_name')
                    ->searchable(),
                TextColumn::make('apps_installed_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('storeleads_id')
                    ->searchable(),
                TextColumn::make('storeleads_payload_hash')
                    ->searchable(),
                TextColumn::make('storeleads_synced_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scraping_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
