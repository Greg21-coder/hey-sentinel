<?php

namespace App\Filament\Resources\StoreReviews;

use App\Filament\Resources\StoreReviews\Pages\CreateStoreReview;
use App\Filament\Resources\StoreReviews\Pages\EditStoreReview;
use App\Filament\Resources\StoreReviews\Pages\ListStoreReviews;
use App\Filament\Resources\StoreReviews\Schemas\StoreReviewForm;
use App\Filament\Resources\StoreReviews\Tables\StoreReviewsTable;
use App\Models\StoreReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StoreReviewResource extends Resource
{
    protected static ?string $model = StoreReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StoreReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreReviews::route('/'),
            'create' => CreateStoreReview::route('/create'),
            'edit' => EditStoreReview::route('/{record}/edit'),
        ];
    }
}
