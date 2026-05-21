<?php

namespace App\Filament\Widgets;

use App\Models\AiPainPoint;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopPainPointsTable extends BaseWidget
{
    protected static ?string $heading = 'Top Pain Points (across all reviews)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->defaultSort('occurrences', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Pain Point')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pricing' => 'danger',
                        'support' => 'warning',
                        'performance' => 'info',
                        'ux' => 'primary',
                        'reliability' => 'danger',
                        'features' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('occurrences')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('apps_affected')
                    ->label('Apps Affected')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_confidence')
                    ->label('Avg Confidence')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state * 100, 0).'%' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('high_severity_count')
                    ->label('High Severity')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function query(): Builder
    {
        return AiPainPoint::query()
            ->select('ai_pain_points.*')
            ->selectSub(
                DB::table('review_pain_point')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->selectRaw('COUNT(*)'),
                'occurrences'
            )
            ->selectSub(
                DB::table('review_pain_point')
                    ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->selectRaw('COUNT(DISTINCT store_reviews.shopify_app_id)'),
                'apps_affected'
            )
            ->selectSub(
                DB::table('review_pain_point')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->selectRaw('AVG(confidence)'),
                'avg_confidence'
            )
            ->selectSub(
                DB::table('review_pain_point')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->where('severity', 'high')
                    ->selectRaw('COUNT(*)'),
                'high_severity_count'
            )
            ->having('occurrences', '>', 0);
    }
}
