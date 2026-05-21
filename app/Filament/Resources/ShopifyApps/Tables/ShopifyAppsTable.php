<?php

namespace App\Filament\Resources\ShopifyApps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShopifyAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shopify_app_handle')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('developer_name')
                    ->searchable(),
                TextColumn::make('developer_url')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('pricing_min_usd')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('pricing_has_free')
                    ->boolean(),
                TextColumn::make('avatar_url')
                    ->searchable(),
                TextColumn::make('average_rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_reviews')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_installs_estimate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('scraping_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('last_scraped_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ai_processed_at')
                    ->dateTime()
                    ->sortable(),
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
