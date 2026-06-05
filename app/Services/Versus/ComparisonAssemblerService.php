<?php

namespace App\Services\Versus;

use App\Enums\AiStatus;
use App\Models\AppComparisonSummary;
use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Support\Collection;

class ComparisonAssemblerService
{
    public function assemble(ShopifyApp $mineApp, Collection $competitors, int $accountId): array
    {
        $allApps = collect([$mineApp])->concat($competitors)->values();

        $columns = $allApps->map(function (ShopifyApp $app) use ($mineApp) {
            return [
                'app' => $this->shapeApp($app),
                'is_mine' => $app->id === $mineApp->id,
                'category_mismatch' => $app->category_id !== $mineApp->category_id,
                'features_pending' => $app->features_json === null,
            ];
        })->all();

        $rows = [
            $this->ratingRow($allApps),
            $this->totalReviewsRow($allApps),
            $this->sentimentRow($allApps),
            $this->painPointsRow($allApps),
            $this->pricingRow($allApps),
            $this->featuresRow($allApps),
        ];

        $cached = AppComparisonSummary::where('account_id', $accountId)
            ->where('mine_shopify_app_id', $mineApp->id)
            ->where('competitor_ids_hash', AppComparisonSummary::hashFor($competitors->pluck('id')->all()))
            ->first();

        return [
            'mine' => $this->shapeApp($mineApp),
            'competitors' => $competitors->map(fn ($a) => $this->shapeApp($a))->all(),
            'columns' => $columns,
            'rows' => $rows,
            'cached_summary' => $cached ? $this->shapeSummary($cached) : null,
        ];
    }

    protected function shapeApp(ShopifyApp $app): array
    {
        return [
            'id' => $app->id,
            'name' => $app->name,
            'developer_name' => $app->developer_name,
            'avatar_url' => $app->avatar_url,
            'shopify_app_handle' => $app->shopify_app_handle,
            'category_id' => $app->category_id,
            'average_rating' => (float) $app->average_rating,
            'total_reviews' => (int) $app->total_reviews,
            'pricing_has_free' => (bool) $app->pricing_has_free,
            'pricing_min_usd' => $app->pricing_min_usd === null ? null : (float) $app->pricing_min_usd,
            'pricing_structured' => $app->pricing_structured,
            'features_json' => $app->features_json,
        ];
    }

    protected function shapeSummary(AppComparisonSummary $row): array
    {
        return [
            'summary' => $row->summary,
            'winner_shopify_app_id' => $row->winner_shopify_app_id,
            'winner_reasoning' => $row->winner_reasoning,
            'per_metric_comments' => $row->per_metric_comments,
            'model' => $row->model,
            'prompt_version' => $row->prompt_version,
            'generated_at' => $row->generated_at?->toISOString(),
        ];
    }

    protected function ratingRow(Collection $apps): array
    {
        $values = $apps->map(fn ($a) => (float) $a->average_rating)->all();
        $max = max($values);
        $winner = null;
        $maxIdx = null;
        foreach ($values as $i => $v) {
            if (abs($v - $max) < 0.001) {
                $maxIdx = $i;
                break;
            }
        }
        $secondHighest = collect($values)->reject(fn ($v, $i) => $i === $maxIdx)->max();
        if ($secondHighest !== null && ($max - $secondHighest) >= 0.10) {
            $winner = $maxIdx;
        }

        return ['metric' => 'rating', 'values' => $values, 'winner_index' => $winner];
    }

    protected function totalReviewsRow(Collection $apps): array
    {
        $values = $apps->map(fn ($a) => (int) $a->total_reviews)->all();
        $maxIdx = collect($values)->search(max($values));
        $second = collect($values)->reject(fn ($v, $i) => $i === $maxIdx)->max();
        $winner = null;
        if ($second !== null && $second > 0) {
            $ratio = max($values) / $second;
            if ($ratio >= 0.5 && $ratio <= 2) {
                $winner = $maxIdx;
            }
        }

        return ['metric' => 'total_reviews', 'values' => $values, 'winner_index' => $winner];
    }

    protected function sentimentRow(Collection $apps): array
    {
        $values = $apps->map(function (ShopifyApp $app) {
            $counts = StoreReview::query()
                ->where('shopify_app_id', $app->id)
                ->where('ai_status', AiStatus::Processed->value)
                ->selectRaw('ai_sentiment, COUNT(*) as c')
                ->groupBy('ai_sentiment')
                ->pluck('c', 'ai_sentiment')
                ->all();
            $total = array_sum($counts) ?: 1;

            return [
                'positive' => (int) round(100 * ($counts['positive'] ?? 0) / $total),
                'neutral' => (int) round(100 * ($counts['neutral'] ?? 0) / $total),
                'mixed' => (int) round(100 * ($counts['mixed'] ?? 0) / $total),
                'negative' => (int) round(100 * ($counts['negative'] ?? 0) / $total),
            ];
        })->all();

        $posScores = collect($values)->pluck('positive')->all();
        $maxIdx = collect($posScores)->search(max($posScores));
        $second = collect($posScores)->reject(fn ($v, $i) => $i === $maxIdx)->max();
        $winner = null;
        if ($second !== null && (max($posScores) - $second) >= 5) {
            $winner = $maxIdx;
        }

        return ['metric' => 'sentiment', 'values' => $values, 'winner_index' => $winner];
    }

    protected function painPointsRow(Collection $apps): array
    {
        $perApp = $apps->map(function (ShopifyApp $app) {
            return StoreReview::query()
                ->where('shopify_app_id', $app->id)
                ->where('ai_status', AiStatus::Processed->value)
                ->whereNotNull('ai_pain_points_json')
                ->get(['ai_pain_points_json'])
                ->flatMap(fn ($r) => json_decode((string) $r->ai_pain_points_json, true) ?: [])
                ->countBy(fn ($pp) => is_string($pp) ? $pp : (string) ($pp['name'] ?? ''))
                ->sortDesc()
                ->take(8);
        });

        $values = $perApp->map(fn ($c) => $c->all())->all();
        $counts = $perApp->map(fn ($c) => $c->sum())->all();

        $minIdx = collect($counts)->search(min($counts));
        $second = collect($counts)->reject(fn ($v, $i) => $i === $minIdx)->min();
        $winner = null;
        if ($second !== null && $second > 0) {
            $relDiff = abs(min($counts) - $second) / $second;
            if ($relDiff >= 0.20) {
                $winner = $minIdx;
            }
        }

        return ['metric' => 'pain_points', 'values' => $values, 'winner_index' => $winner];
    }

    protected function pricingRow(Collection $apps): array
    {
        $values = $apps->map(fn ($a) => $a->pricing_structured)->all();
        $isFree = $apps->map(fn ($a) => (bool) $a->pricing_has_free)->all();
        $minUsd = $apps->map(fn ($a) => $a->pricing_min_usd === null ? null : (float) $a->pricing_min_usd)->all();

        $winner = null;
        $freeIndexes = array_keys(array_filter($isFree));
        if (count($freeIndexes) === 1) {
            $winner = $freeIndexes[0];
        } elseif (count($freeIndexes) === 0) {
            $defined = array_filter($minUsd, fn ($v) => $v !== null);
            if (count($defined) >= 2) {
                $minPrice = min($defined);
                $minPriceIdx = array_search($minPrice, $minUsd);
                $second = collect($defined)->reject(fn ($v, $i) => $i === $minPriceIdx)->min();
                if ($second !== null && ($second - $minPrice) >= 5) {
                    $winner = $minPriceIdx;
                }
            }
        }

        return ['metric' => 'pricing', 'values' => $values, 'winner_index' => $winner];
    }

    protected function featuresRow(Collection $apps): array
    {
        $values = $apps->map(fn ($a) => $a->features_json ?? [])->all();
        $names = collect($values)->map(fn ($list) => collect($list)
            ->filter(fn ($f) => (float) ($f['confidence'] ?? 0) >= 0.5)
            ->pluck('name')
            ->unique()
            ->all());
        $counts = $names->map(fn ($n) => count($n))->all();

        $allNames = $names->flatten()->unique()->values();
        $matrix = $allNames->map(function ($name) use ($names) {
            return [
                'name' => $name,
                'presence' => $names->map(fn ($list) => in_array($name, $list, true))->all(),
            ];
        })->all();

        $maxIdx = collect($counts)->search(max($counts));
        $second = collect($counts)->reject(fn ($v, $i) => $i === $maxIdx)->max();
        $winner = null;
        if ($second !== null && (max($counts) - $second) >= 2) {
            $winner = $maxIdx;
        }

        return ['metric' => 'features', 'values' => $values, 'winner_index' => $winner, 'feature_matrix' => $matrix];
    }
}
