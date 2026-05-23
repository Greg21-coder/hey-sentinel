<?php

namespace App\Filament\Customer\Resources\Apps\Tables;

use App\Enums\FollowedAppKind;
use App\Models\AccountFollowedApp;
use App\Models\ShopifyApp;
use App\Models\ShopifyAppCategory;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('total_reviews', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('App')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge(),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('★')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_reviews')
                    ->label('Reviews')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_followed')
                    ->label('Following')
                    ->state(function (ShopifyApp $record): bool {
                        $accountId = Auth::user()?->currentAccount?->id;
                        if ($accountId === null) {
                            return false;
                        }
                        return AccountFollowedApp::query()
                            ->where('account_id', $accountId)
                            ->where('shopify_app_id', $record->id)
                            ->exists();
                    })
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => ShopifyAppCategory::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('toggleFollow')
                    ->label(function (ShopifyApp $record): string {
                        $accountId = Auth::user()?->currentAccount?->id;
                        $following = AccountFollowedApp::query()
                            ->where('account_id', $accountId)
                            ->where('shopify_app_id', $record->id)
                            ->exists();
                        return $following ? 'Unfollow' : 'Follow';
                    })
                    ->color(function (ShopifyApp $record): string {
                        $accountId = Auth::user()?->currentAccount?->id;
                        $following = AccountFollowedApp::query()
                            ->where('account_id', $accountId)
                            ->where('shopify_app_id', $record->id)
                            ->exists();
                        return $following ? 'gray' : 'primary';
                    })
                    ->action(function (ShopifyApp $record): void {
                        $accountId = Auth::user()?->currentAccount?->id;
                        if ($accountId === null) {
                            return;
                        }

                        $existing = AccountFollowedApp::query()
                            ->where('account_id', $accountId)
                            ->where('shopify_app_id', $record->id)
                            ->first();

                        if ($existing !== null) {
                            $existing->delete();
                            return;
                        }

                        AccountFollowedApp::create([
                            'account_id' => $accountId,
                            'shopify_app_id' => $record->id,
                            'kind' => FollowedAppKind::Competitor->value,
                            'followed_at' => now(),
                        ]);
                    }),
            ])
            ->toolbarActions([]);
    }
}
