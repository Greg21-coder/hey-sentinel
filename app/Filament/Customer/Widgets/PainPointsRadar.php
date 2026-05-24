<?php

namespace App\Filament\Customer\Widgets;

use App\Models\AccountFollowedApp;
use App\Models\AiPainPoint;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PainPointsRadar extends BaseWidget
{
    protected static ?string $heading = 'Pain points radar';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Compute once per render; formatStateUsing closure captures the scalar
        // so we do not fire a COUNT query per row.
        $totalFollowedApps = $this->totalFollowedApps();

        return $table
            ->query($this->query())
            ->defaultSort('mentions', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Pain point')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Description')
                    ->wrap(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('mentions')
                    ->label('Mentions')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apps_count')
                    ->label('Apps')
                    ->formatStateUsing(fn ($state) => $state.' / '.$totalFollowedApps)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn () => AiPainPoint::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->all())
                    ->multiple(),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function query(): Builder
    {
        $appIds = $this->followedAppIds();

        $mentionsSub = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
            ->whereIn('store_reviews.shopify_app_id', $appIds)
            ->selectRaw('COUNT(*)');

        $appsCountSub = DB::table('review_pain_point')
            ->join('store_reviews', 'store_reviews.id', '=', 'review_pain_point.review_id')
            ->whereColumn('review_pain_point.pain_point_id', 'ai_pain_points.id')
            ->whereIn('store_reviews.shopify_app_id', $appIds)
            ->selectRaw('COUNT(DISTINCT store_reviews.shopify_app_id)');

        return AiPainPoint::query()
            ->select('ai_pain_points.*')
            ->selectSub($mentionsSub, 'mentions')
            ->selectSub($appsCountSub, 'apps_count')
            ->having('mentions', '>', 0);
    }

    /**
     * Explicit account-scoped lookup. Do not rely on the BelongsToAccount
     * global scope alone — it silently skips when currentAccount is null
     * (e.g., user with no account_user pivot), which would leak across
     * tenants. ?? 0 forces an empty result in that edge case.
     */
    protected function followedAppIds(): \Illuminate\Support\Collection
    {
        $accountId = Auth::user()?->currentAccount?->id ?? 0;

        return AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->pluck('shopify_app_id');
    }

    protected function totalFollowedApps(): int
    {
        $accountId = Auth::user()?->currentAccount?->id ?? 0;

        return AccountFollowedApp::query()
            ->where('account_id', $accountId)
            ->distinct()
            ->count('shopify_app_id');
    }
}
