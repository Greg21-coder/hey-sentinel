<?php

namespace App\Filament\Resources\StoreReviews;

use App\Filament\Resources\StoreReviews\Pages\ListStoreReviews;
use App\Filament\Resources\StoreReviews\Tables\StoreReviewsTable;
use App\Models\StoreReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StoreReviewResource extends Resource
{
    protected static ?string $model = StoreReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static \UnitEnum|string|null $navigationGroup = 'Datos';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return StoreReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreReviews::route('/'),
        ];
    }
}
