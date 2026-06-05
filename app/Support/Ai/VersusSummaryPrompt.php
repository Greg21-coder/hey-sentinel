<?php

namespace App\Support\Ai;

class VersusSummaryPrompt
{
    public const VERSION = 'v1-versus-2026-06';

    public static function system(): string
    {
        return <<<'PROMPT'
You compare Shopify apps for an app developer evaluating competitors in the same niche.

Output JSON ONLY:
{
  "winner_app_id": <int or null>,
  "winner_reasoning": "<2 to 3 sentences>",
  "per_metric_comments": {
    "rating": "<one sentence>",
    "pricing": "<one sentence>",
    "features": "<one sentence>",
    "pain_points": "<one sentence>"
  }
}

Rules:
- Pick a winner only if the metrics support it. Otherwise winner_app_id is null.
- Always write in English.
- Be specific. Cite metrics (e.g., "4.8 vs 4.5 rating", "free tier vs \$9/mo").
- If any column is flagged "category_mismatch=true" in the input, mention in winner_reasoning that the comparison may not be precise.
- No marketing language. No preamble. No closing.
PROMPT;
    }

    public static function userMessage(array $payload): string
    {
        $lines = [];
        foreach ($payload['columns'] as $i => $col) {
            $a = $col['app'];
            $lines[] = '---';
            $lines[] = "Column {$i} (app_id={$a['id']}, is_mine=".($col['is_mine'] ? 'true' : 'false').", category_mismatch=".($col['category_mismatch'] ? 'true' : 'false').")";
            $lines[] = "Name: {$a['name']}";
            $lines[] = "Rating: {$a['average_rating']} ({$a['total_reviews']} reviews)";
            $lines[] = 'Pricing: '.($a['pricing_has_free'] ? 'free tier' : '\$'.($a['pricing_min_usd'] ?? 'unknown').' min');
            $features = collect($a['features_json'] ?? [])->pluck('name')->take(10)->implode(', ');
            $lines[] = "Top features: {$features}";
        }
        $lines[] = '';
        $painRow = collect($payload['rows'])->firstWhere('metric', 'pain_points');
        if ($painRow) {
            foreach ($painRow['values'] as $i => $counts) {
                $top = collect($counts)->take(5)->keys()->implode(', ');
                $lines[] = "Pain points col {$i}: {$top}";
            }
        }

        return implode("\n", $lines);
    }
}
