<?php

namespace App\Filament\Resources\StoreReviews\Tables;

use App\Enums\AiStatus;
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
                    ->color(fn (AiStatus $state): string => match ($state) {
                        AiStatus::Processed => 'success',
                        AiStatus::Batched => 'warning',
                        AiStatus::Error => 'danger',
                        AiStatus::Skipped => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('ai_sentiment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        'mixed' => 'warning',
                        'neutral' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('painPoints.slug')
                    ->label('Pain points')
                    ->badge()
                    ->color('info')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('—'),
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
