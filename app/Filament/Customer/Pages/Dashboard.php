<?php

namespace App\Filament\Customer\Pages;

use App\Filament\Customer\Widgets\FollowedAppSummaries;
use App\Filament\Customer\Widgets\MyAppsList;
use App\Filament\Customer\Widgets\MyAppsStatsOverview;
use App\Filament\Customer\Widgets\PainPointsRadar;
use App\Filament\Customer\Widgets\SentimentTimelineChart;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = '/';

    protected static ?int $navigationSort = 0;

    public function getWidgets(): array
    {
        return [
            MyAppsStatsOverview::class,
            FollowedAppSummaries::class,
            SentimentTimelineChart::class,
            PainPointsRadar::class,
            MyAppsList::class,
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
