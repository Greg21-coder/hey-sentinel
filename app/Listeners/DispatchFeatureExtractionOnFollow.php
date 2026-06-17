<?php

namespace App\Listeners;

use App\Events\AppFollowed;
use App\Jobs\Ai\ExtractAppFeaturesJob;

class DispatchFeatureExtractionOnFollow
{
    public function handle(AppFollowed $event): void
    {
        if ($event->app->features_json !== null) {
            return;
        }

        ExtractAppFeaturesJob::dispatch($event->app->id);
    }
}
