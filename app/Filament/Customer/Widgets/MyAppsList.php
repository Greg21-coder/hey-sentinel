<?php

namespace App\Filament\Customer\Widgets;

use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MyAppsList extends BaseWidget
{
    protected static ?string $heading = 'My followed apps';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->defaultSort('total_reviews', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('App')
                    ->searchable()
                    ->wrap()
                    ->url(fn (ShopifyApp $record) => route('filament.customer.resources.apps.view', ['record' => $record])),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('★')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_reviews')
                    ->label('Reviews')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot_kind')
                    ->label('Kind')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'mine' ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => $state === 'mine' ? 'My app' : 'Competitor'),

                Tables\Columns\TextColumn::make('pivot_followed_at')
                    ->label('Following since')
                    ->date()
                    ->sortable(),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function query(): Builder
    {
        $followedIds = AccountFollowedApp::query()->pluck('shopify_app_id');

        return ShopifyApp::query()
            ->whereIn('shopify_apps.id', $followedIds)
            ->join('account_followed_apps', 'account_followed_apps.shopify_app_id', '=', 'shopify_apps.id')
            ->select('shopify_apps.*', 'account_followed_apps.kind as pivot_kind', 'account_followed_apps.followed_at as pivot_followed_at');
    }
}
