<?php

namespace App\Filament\Resources\ShopifyApps\RelationManagers;

use App\Enums\AiStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Reviews';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reviewer_name')
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('rating')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),
                TextColumn::make('reviewer_name')
                    ->label('Reviewer')
                    ->searchable()
                    ->limit(24),
                TextColumn::make('review_text')
                    ->label('Review')
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->review_text)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('ai_sentiment')
                    ->label('Sentiment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        'mixed' => 'warning',
                        'neutral' => 'gray',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('ai_status')
                    ->label('AI')
                    ->badge()
                    ->color(fn (AiStatus $state): string => match ($state) {
                        AiStatus::Processed => 'success',
                        AiStatus::Batched => 'warning',
                        AiStatus::Error => 'danger',
                        AiStatus::Skipped => 'gray',
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
                TextColumn::make('published_at')
                    ->label('Published')
                    ->date()
                    ->sortable(),
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
                SelectFilter::make('pain_points')
                    ->label('Pain point')
                    ->relationship('painPoints', 'slug')
                    ->multiple()
                    ->searchable()
                    ->preload(),
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
            ])
            ->filtersFormColumns(2)
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
