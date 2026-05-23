<?php

namespace App\Filament\Customer\Resources\Apps\Pages;

use App\Filament\Customer\Resources\Apps\AppResource;
use App\Filament\Customer\Resources\Apps\Widgets\AppPainPointsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewApp extends ViewRecord
{
    protected static string $resource = AppResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Overview';
    }

    protected function getFooterWidgets(): array
    {
        return [
            AppPainPointsWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [
            AppPainPointsWidget::class => ['record' => $this->record],
        ];
    }
}
