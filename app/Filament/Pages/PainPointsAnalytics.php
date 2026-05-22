<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PainPointsStatsOverview;
use App\Filament\Widgets\TopAppsByPainPointsTable;
use App\Filament\Widgets\TopPainPointsTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PainPointsAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $title = 'Pain Points Analytics';

    protected static ?string $navigationLabel = 'Pain Points';

    protected static \UnitEnum|string|null $navigationGroup = 'IA';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            PainPointsStatsOverview::class,
            TopPainPointsTable::class,
            TopAppsByPainPointsTable::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)->schema(
                fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())
            ),
        ]);
    }
}
