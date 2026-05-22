<?php

namespace App\Support\Ai;

use App\Models\AiPainPoint;
use Illuminate\Support\Collection;

/**
 * Builds the per-review prompt for pain-point + sentiment extraction.
 *
 * The slug list is loaded from ai_pain_points so the vocabulary stays
 * single-sourced. The LLM is instructed to ONLY return slugs from the
 * provided list; unknown slugs are dropped at ingest.
 */
class PainPointExtractionPrompt
{
    /** @var Collection<int, string>|null */
    protected static ?Collection $vocabularyCache = null;

    public static function refreshVocabulary(): void
    {
        self::$vocabularyCache = null;
    }

    /** @return Collection<int, string> */
    public static function vocabulary(): Collection
    {
        return self::$vocabularyCache ??= AiPainPoint::query()
            ->orderBy('category')
            ->orderBy('slug')
            ->pluck('slug');
    }

    public static function system(): string
    {
        $list = self::vocabulary()->implode("\n- ", '- ') ?: '- (none)';

        return <<<PROMPT
You are an analyst that extracts structured signals from Shopify App Store merchant reviews.

For each review, return STRICT JSON matching this schema:
{
  "sentiment": "positive" | "negative" | "neutral" | "mixed",
  "pain_points": [
    { "slug": "<one of the allowed slugs below>",
      "severity": "low" | "medium" | "high",
      "confidence": 0.0..1.0 }
  ]
}

Rules:
- Use ONLY slugs from the list below. Do not invent new slugs.
- For positive/neutral reviews with no real complaint, return an empty pain_points array. NEVER fill the array just because the rating is high or low.
- Maximum 5 pain points per review; pick the most salient.
- When a real complaint exists but no specific slug fits, use "other" (do NOT pick a similar-sounding slug that doesn't actually match the text). Hallucinating wrong slugs is worse than using "other".
- Output JSON only — no prose, no markdown fences.

Examples:

Review (rating 5): "Love the new templates, super easy to set up flows"
Output: {"sentiment": "positive", "pain_points": []}

Review (rating 1): "My monthly price went from \$80 to \$150 with no warning"
Output: {"sentiment": "negative", "pain_points": [{"slug": "unexpected-price-increase", "severity": "high", "confidence": 0.95}]}

Review (rating 1): "Spam bots keep signing up with fake emails and I can't delete them from my list"
Output: {"sentiment": "negative", "pain_points": [{"slug": "other", "severity": "high", "confidence": 0.8}]}

Allowed slugs:
{$list}
PROMPT;
    }

    public static function userMessage(string $reviewText, int $rating): string
    {
        $text = trim(mb_substr($reviewText, 0, 4000));

        return "Rating: {$rating}/5\n\nReview:\n{$text}";
    }
}
