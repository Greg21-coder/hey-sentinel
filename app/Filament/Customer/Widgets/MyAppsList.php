<?php

namespace App\Filament\Customer\Widgets;

use App\Models\ShopifyApp;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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

                Tables\Columns\TextColumn::make('ai_summary')
                    ->label('AI Summary')
                    ->wrap()
                    ->limit(90)
                    ->placeholder('—')
                    ->tooltip(fn (ShopifyApp $record): ?string => $record->ai_summary)
                    ->action(
                        Action::make('viewAiSummary')
                            ->modalHeading(fn (ShopifyApp $record): string => 'AI Summary — '.$record->name)
                            ->modalContent(fn (ShopifyApp $record) => view('filament.modals.ai-summary', ['record' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->visible(fn (ShopifyApp $record): bool => filled($record->ai_summary)),
                    ),

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
        $accountId = Auth::user()?->currentAccount?->id ?? 0;

        return ShopifyApp::query()
            ->join('account_followed_apps', function ($join) use ($accountId) {
                $join->on('account_followed_apps.shopify_app_id', '=', 'shopify_apps.id')
                    ->where('account_followed_apps.account_id', $accountId);
            })
            ->select(
                'shopify_apps.*',
                'account_followed_apps.kind as pivot_kind',
                'account_followed_apps.followed_at as pivot_followed_at',
            );
    }
}
