<?php

namespace App\Filament\Resources\StoreReviews\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shopify_app_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shopify_store_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reviewer_name')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_text_hash')
                    ->searchable(),
                TextColumn::make('language_code')
                    ->searchable(),
                TextColumn::make('ai_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('ai_sentiment')
                    ->searchable(),
                TextColumn::make('ai_processed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
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
