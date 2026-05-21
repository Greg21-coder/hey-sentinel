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
- Use ONLY slugs from the list below. If no listed pain point applies, return an empty pain_points array.
- Do not invent new slugs.
- Maximum 5 pain points per review; pick the most salient.
- Output JSON only — no prose, no markdown fences.

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
