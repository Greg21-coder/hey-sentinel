<?php

namespace App\Filament\Resources\Plans\RelationManagers;

use App\Enums\FeatureValueType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('feature_key')
                    ->required()
                    ->maxLength(80),
                TextInput::make('feature_value')
                    ->required()
                    ->maxLength(255),
                Select::make('value_type')
                    ->options(FeatureValueType::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('feature_key')
            ->columns([
                TextColumn::make('feature_key')->searchable(),
                TextColumn::make('feature_value'),
                TextColumn::make('value_type')->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
