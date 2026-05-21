<?php

namespace App\Filament\Widgets;

use App\Models\ShopifyApp;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopAppsByPainPointsTable extends BaseWidget
{
    protected static ?string $heading = 'Apps Ranked by Pain Point Signal';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->defaultSort('pain_point_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('App')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('developer_name')
                    ->label('Developer')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('★')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_reviews')
                    ->label('Reviews')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_reviews')
                    ->label('Analyzed')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pain_point_count')
                    ->label('Pain Signals')
                    ->badge()
                    ->color('warning')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('top_pain_point')
                    ->label('Top Pain Point')
                    ->badge()
                    ->color('danger')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('negative_pct')
                    ->label('Negative %')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0).'%' : '—'),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function query(): Builder
    {
        $processedSub = DB::table('store_reviews')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->where('ai_status', 'processed')
            ->selectRaw('COUNT(*)');

        $painCountSub = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->selectRaw('COUNT(*)');

        $topPainSub = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->join('ai_pain_points', 'ai_pain_points.id', '=', 'review_pain_point.pain_point_id')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->selectRaw('ai_pain_points.slug')
            ->groupBy('ai_pain_points.slug')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1);

        $negativePctSub = DB::table('store_reviews')
            ->whereColumn('store_reviews.shopify_app_id', 'shopify_apps.id')
            ->where('ai_status', 'processed')
            ->selectRaw('ROUND(100.0 * SUM(CASE WHEN ai_sentiment = "negative" THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 1)');

        return ShopifyApp::query()
            ->select('shopify_apps.*')
            ->selectSub($processedSub, 'processed_reviews')
            ->selectSub($painCountSub, 'pain_point_count')
            ->selectSub($topPainSub, 'top_pain_point')
            ->selectSub($negativePctSub, 'negative_pct')
            ->having('pain_point_count', '>', 0);
    }
}
