<?php

namespace App\Services\Intelligence;

use App\Models\AppSnapshot;
use App\Models\ShopifyApp;

class SnapshotDiffService
{
    public function createSnapshot(ShopifyApp $app): AppSnapshot
    {
        return AppSnapshot::create([
            'shopify_app_id' => $app->id,
            'name' => $app->name ?? '',
            'developer_name' => $app->developer_name ?? '',
            'description_hash' => $app->description ? hash('sha256', $app->description) : null,
            'pricing_raw' => $app->pricing_raw,
            'pricing_min_usd' => $app->pricing_min_usd,
            'pricing_has_free' => (bool) $app->pricing_has_free,
            'average_rating' => $app->average_rating ?? 0,
            'total_reviews' => $app->total_reviews ?? 0,
            'category_name' => $app->category?->name,
            'avatar_url' => $app->avatar_url,
            'snapshot_at' => now(),
        ]);
    }

    /** @return list<array{field: string, old: mixed, new: mixed}> */
    public function diff(AppSnapshot $previous, AppSnapshot $current): array
    {
        $trackedFields = config('scraping.intelligence.tracked_fields', []);
        $ratingThreshold = (float) config('scraping.intelligence.rating_change_threshold', 0.05);

        $changes = [];

        foreach ($trackedFields as $field) {
            $oldVal = $previous->getAttribute($field);
            $newVal = $current->getAttribute($field);

            if ($field === 'average_rating') {
                if (abs((float) $oldVal - (float) $newVal) < $ratingThreshold) {
                    continue;
                }
            }

            if ($this->valuesAreDifferent($oldVal, $newVal)) {
                $changes[] = [
                    'field' => $field,
                    'old' => $this->castToString($oldVal),
                    'new' => $this->castToString($newVal),
                ];
            }
        }

        return $changes;
    }

    protected function valuesAreDifferent(mixed $old, mixed $new): bool
    {
        if ($old === null && $new === null) {
            return false;
        }

        return (string) $old !== (string) $new;
    }

    protected function castToString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
