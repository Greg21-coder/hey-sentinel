<?php

namespace App\Filament\Customer\Resources\Apps\Widgets;

use App\Models\ShopifyApp;
use Filament\Widgets\Widget;

class AppSummaryWidget extends Widget
{
    protected string $view = 'filament.customer.widgets.app-summary';

    protected int|string|array $columnSpan = 'full';

    public ?ShopifyApp $record = null;
}
