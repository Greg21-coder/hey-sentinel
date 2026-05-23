<?php

namespace App\Filament\Customer\Resources\Apps\Widgets;

use App\Models\ShopifyApp;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AppPainPointsWidget extends BaseWidget
{
    protected static ?string $heading = 'Pain points detected';

    protected int|string|array $columnSpan = 'full';

    public ?ShopifyApp $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->defaultSort('mentions', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Pain point')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Description'),
                Tables\Columns\TextColumn::make('mentions')
                    ->label('Mentions')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function query(): Builder
    {
        $appId = $this->record?->id ?? 0;

        return \App\Models\AiPainPoint::query()
            ->select('ai_pain_points.*')
            ->selectSub(
                DB::table('review_pain_point')
                    ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
                    ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
                    ->where('store_reviews.shopify_app_id', $appId)
                    ->selectRaw('COUNT(*)'),
                'mentions'
            )
            ->having('mentions', '>', 0);
    }
}
