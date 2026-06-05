<?php

namespace App\Support\Ai;

use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Support\Collection;

class AppFeaturePrompt
{
    public const VERSION = 'v1-features-2026-06';
    public const SAMPLE_SIZE = 15;
    public const MIN_SAMPLE_SIZE = 3;

    public static function system(): string
    {
        return <<<'PROMPT'
You extract product features from a Shopify app's description and customer reviews.

Rules:
- Output JSON ONLY: {"features": [{"name": string, "category": string, "confidence": number}, ...]}.
- Produce 8 to 15 features. No fewer than 8 if signal exists, no more than 15.
- "name" is a concrete capability (e.g., "A/B testing", "Klaviyo integration", "Bulk product import"). Never marketing prose.
- "category" is one of: Integrations, Analytics, Automation, UI, Pricing, Other.
- "confidence" is 0.00 to 1.00 by how clearly the feature is stated.
- Always write feature names in English.
- Do not invent features not supported by the description or reviews.
PROMPT;
    }

    /**
     * @param  Collection<int, StoreReview>  $reviews
     */
    public static function userMessage(ShopifyApp $app, Collection $reviews): string
    {
        $lines = [];
        $lines[] = "App name: {$app->name}";
        $lines[] = "Developer: {$app->developer_name}";
        $lines[] = 'Description:';
        $lines[] = trim((string) $app->description);
        $lines[] = '';
        $lines[] = 'Recent processed reviews (rating in brackets, then review text):';
        $lines[] = '';

        foreach ($reviews as $review) {
            $text = trim((string) $review->review_text);
            $text = mb_substr($text, 0, 400);
            $rating = (int) $review->rating;
            $lines[] = "[{$rating}★] {$text}";
        }

        return implode("\n", $lines);
    }
}
