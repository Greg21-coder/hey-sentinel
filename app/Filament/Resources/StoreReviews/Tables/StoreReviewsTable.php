<?php

namespace App\Filament\Resources\StoreReviews\Tables;

use App\Enums\AiStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoreReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('shopify_app_id')
                    ->label('App #')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shopify_store_id')
                    ->label('Store #')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('reviewer_name')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_text')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->review_text)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('review_text_hash')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    }),
                TextColumn::make('ai_sentiment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        'mixed' => 'warning',
                        'neutral' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('painPoints.slug')
                    ->label('Pain points')
                    ->badge()
                    ->color('info')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('—')
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
                SelectFilter::make('ai_status')
                    ->options(AiStatus::class)
                    ->multiple(),
                SelectFilter::make('ai_sentiment')
                    ->options([
                        'positive' => 'Positive',
                        'negative' => 'Negative',
                        'neutral' => 'Neutral',
                        'mixed' => 'Mixed',
                    ])
                    ->multiple(),
                SelectFilter::make('rating')
                    ->options([
                        1 => '★',
                        2 => '★★',
                        3 => '★★★',
                        4 => '★★★★',
                        5 => '★★★★★',
                    ])
                    ->multiple(),
                SelectFilter::make('app')
                    ->label('App')
                    ->relationship('app', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('pain_points')
                    ->label('Pain point')
                    ->relationship('painPoints', 'slug')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('language_code')
                    ->label('Language')
                    ->options(fn () => \App\Models\StoreReview::query()
                        ->whereNotNull('language_code')
                        ->distinct()
                        ->pluck('language_code', 'language_code')
                        ->all())
                    ->multiple(),
                Filter::make('published_at_range')
                    ->label('Published between')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('published_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('published_at', '<=', $d))
                    ),
                Filter::make('ai_processed_at_range')
                    ->label('AI processed between')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('ai_processed_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('ai_processed_at', '<=', $d))
                    ),
            ])
            ->filtersFormColumns(2)
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
