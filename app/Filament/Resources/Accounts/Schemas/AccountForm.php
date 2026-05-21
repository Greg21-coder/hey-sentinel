<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Enums\AccountStatus;
use App\Enums\BillingCycle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('status')
                    ->options(AccountStatus::class)
                    ->default('trial')
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required(),
                Select::make('billing_cycle')
                    ->options(BillingCycle::class)
                    ->default('none')
                    ->required(),
                DateTimePicker::make('trial_started_at'),
                DateTimePicker::make('free_trial_ends_at'),
                DateTimePicker::make('current_period_starts_at'),
                DateTimePicker::make('current_period_ends_at'),
                TextInput::make('stripe_customer_id'),
                TextInput::make('owner_user_id')
                    ->numeric(),
            ]);
    }
}
