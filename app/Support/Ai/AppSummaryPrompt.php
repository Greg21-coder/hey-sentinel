<?php

namespace App\Support\Ai;

use App\Models\ShopifyApp;
use App\Models\StoreReview;
use Illuminate\Support\Collection;

class AppSummaryPrompt
{
    public const VERSION = 'v1-summary-2026-05';

    // Kept small so Ollama 7b on CPU finishes in well under the 180s timeout.
    // 15 recent processed reviews still captures the dominant signal.
    public const SAMPLE_SIZE = 15;

    // Below this, qwen2.5 hallucinates (observed: 1 English review → Chinese summary).
    // 3 is the smallest count where "patterns across reviews" makes sense.
    public const MIN_SAMPLE_SIZE = 3;

    public static function system(): string
    {
        return <<<'PROMPT'
You analyze customer reviews of Shopify apps for an app developer doing market research.

Given an app and a sample of its reviews, write a concise summary that helps the developer understand:
1. What users love most (1 sentence)
2. What users complain about most (1 sentence)
3. One nuance or pattern that stands out (1 sentence)

Rules:
- Always write in English. Do not switch languages.
- 2 to 3 short sentences total. No bullet points. No preamble. No closing.
- Be specific (e.g., "deliverability", "pricing tiers", "Klaviyo integration") instead of vague ("good service", "some issues").
- Reference patterns across reviews, never quote individual reviews.
- Neutral, factual tone. No marketing language. Do not address the reader.
- If the sample is too small or contradictory, say so honestly.
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
        $lines[] = "Average rating: {$app->average_rating} / 5";
        $lines[] = "Total reviews on Shopify: {$app->total_reviews}";
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
