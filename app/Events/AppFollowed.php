<?php

namespace App\Events;

use App\Models\Account;
use App\Models\ShopifyApp;
use Illuminate\Foundation\Events\Dispatchable;

class AppFollowed
{
    use Dispatchable;

    public function __construct(
        public readonly Account $account,
        public readonly ShopifyApp $app,
    ) {
    }
}
