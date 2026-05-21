<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\PlanStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('monthly_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('yearly_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('stripe_monthly_price_id'),
                TextInput::make('stripe_yearly_price_id'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(PlanStatus::class)
                    ->default('active')
                    ->required(),
                Toggle::make('is_public')
                    ->required(),
            ]);
    }
}
