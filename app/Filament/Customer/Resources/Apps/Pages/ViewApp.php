<?php

namespace App\Filament\Customer\Resources\Apps\Pages;

use App\Filament\Customer\Resources\Apps\AppResource;
use App\Filament\Customer\Resources\Apps\Widgets\AppPainPointsWidget;
use App\Filament\Customer\Resources\Apps\Widgets\AppSummaryWidget;
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

    protected function getHeaderWidgets(): array
    {
        return [
            AppSummaryWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
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

    // No getWidgetData() override: the parent InteractsWithRecord trait
    // already returns ['record' => $this->getRecord()], which Filament
    // spreads as a public Livewire prop into each registered widget.
    // A class-keyed shape (introduced in M1) was silently wrong — both
    // header and footer widgets received $record=null and rendered empty.
}
