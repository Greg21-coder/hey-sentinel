<?php

namespace App\Filament\Resources\StoreReviews\Schemas;

use App\Enums\AiStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StoreReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shopify_app_id')
                    ->required()
                    ->numeric(),
                TextInput::make('shopify_store_id')
                    ->numeric(),
                TextInput::make('reviewer_name')
                    ->required(),
                TextInput::make('rating')
                    ->required()
                    ->numeric(),
                Textarea::make('review_text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('review_text_hash')
                    ->required(),
                TextInput::make('language_code'),
                Select::make('ai_status')
                    ->options(AiStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('ai_sentiment'),
                DateTimePicker::make('ai_processed_at'),
                DateTimePicker::make('published_at')
                    ->required(),
            ]);
    }
}
