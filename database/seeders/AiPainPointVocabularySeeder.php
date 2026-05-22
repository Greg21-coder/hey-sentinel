<?php

namespace Database\Seeders;

use App\Models\AiPainPoint;
use Illuminate\Database\Seeder;

class AiPainPointVocabularySeeder extends Seeder
{
    /**
     * Controlled vocabulary for the AI pipeline. The LLM is constrained to
     * pick only from these slugs; unknown slugs are dropped at ingest time.
     *
     * Categories: pricing, support, performance, ux, reliability, features.
     */
    public function run(): void
    {
        $vocabulary = [
            ['pricing-too-high', 'Pricing too high', 'pricing'],
            ['unexpected-price-increase', 'Unexpected price increase', 'pricing'],
            ['hidden-fees', 'Hidden or surprise fees', 'pricing'],
            ['no-free-tier-viable', 'Free tier unusable', 'pricing'],

            ['slow-support-response', 'Slow support response', 'support'],
            ['unhelpful-support', 'Unhelpful or scripted support', 'support'],
            ['no-phone-support', 'No phone or live support', 'support'],

            ['slow-performance', 'Slow performance / page load', 'performance'],
            ['high-resource-usage', 'High store resource usage', 'performance'],

            ['confusing-ux', 'Confusing or cluttered UX', 'ux'],
            ['steep-learning-curve', 'Steep learning curve', 'ux'],
            ['poor-mobile-experience', 'Poor mobile experience', 'ux'],

            ['frequent-bugs', 'Frequent bugs or crashes', 'reliability'],
            ['data-sync-issues', 'Data sync / inventory issues', 'reliability'],
            ['breaks-after-updates', 'Breaks after platform updates', 'reliability'],

            ['missing-key-feature', 'Missing key feature', 'features'],
            ['limited-customization', 'Limited customization', 'features'],
            ['poor-integrations', 'Poor third-party integrations', 'features'],
            ['weak-analytics', 'Weak analytics / reporting', 'features'],
            ['billing-issues', 'Billing or subscription issues', 'pricing'],

            // Escape valve: lets the LLM flag a real complaint that doesn't
            // map to any specific slug, instead of hallucinating a wrong one.
            // Used together with few-shot examples in PainPointExtractionPrompt.
            ['other', 'Other pain (not in vocabulary)', 'other'],
        ];

        foreach ($vocabulary as [$slug, $name, $category]) {
            AiPainPoint::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'category' => $category]
            );
        }
    }
}
