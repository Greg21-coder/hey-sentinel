<?php

namespace App\Services\Scraping;

use Symfony\Component\DomCrawler\Crawler;

class ShopifyAppPageParser
{
    public function parse(string $html): ParsedShopifyApp
    {
        $crawler = new Crawler($html);

        $jsonLd = $this->extractJsonLd($crawler);

        return new ParsedShopifyApp(
            name: $this->cleanString($jsonLd['name'] ?? null),
            developerName: $this->extractDeveloperName($crawler, $jsonLd),
            developerUrl: $this->extractDeveloperUrl($crawler),
            categoryName: $this->extractCategoryName($crawler),
            description: $this->cleanString($jsonLd['description'] ?? null),
            pricingRaw: $this->extractPricingRaw($crawler),
            pricingHasFree: $this->extractPricingHasFree($crawler),
            avatarUrl: $this->extractAvatarUrl($jsonLd),
            averageRating: $this->extractFloat($jsonLd['aggregateRating']['ratingValue'] ?? null),
            totalReviews: $this->extractInt($jsonLd['aggregateRating']['ratingCount'] ?? null),
        );
    }

    protected function extractJsonLd(Crawler $crawler): array
    {
        $node = $crawler->filter('script[type="application/ld+json"]')->first();
        if ($node->count() === 0) {
            return [];
        }

        $data = json_decode($node->text(null, false), true);

        return is_array($data) ? $data : [];
    }

    protected function extractDeveloperName(Crawler $crawler, array $jsonLd): ?string
    {
        $link = $crawler->filter('a[href*="/partners/"]')->first();
        if ($link->count() > 0) {
            return $this->cleanString($link->text(null, true));
        }

        $brand = $jsonLd['brand'] ?? null;
        if (is_array($brand)) {
            return $this->cleanString($brand['name'] ?? null);
        }

        return $this->cleanString($brand);
    }

    protected function extractDeveloperUrl(Crawler $crawler): ?string
    {
        $link = $crawler->filter('a[href*="/partners/"]')->first();

        return $link->count() > 0 ? $link->attr('href') : null;
    }

    protected function extractCategoryName(Crawler $crawler): ?string
    {
        $link = $crawler->filter('a[href*="surface_type=app_details"][href*="/categories/"]')->first();

        return $link->count() > 0 ? $this->cleanString($link->text(null, true)) : null;
    }

    protected function extractPricingRaw(Crawler $crawler): ?string
    {
        $section = $crawler->filter('#adp-pricing')->first();
        if ($section->count() === 0) {
            return null;
        }

        $text = $section->text(null, true);

        return mb_substr($this->cleanString($text) ?? '', 0, 4000) ?: null;
    }

    protected function extractPricingHasFree(Crawler $crawler): bool
    {
        $names = $crawler->filter('#adp-pricing [data-test-id="name"], #adp-pricing [data-test-id="price"]');

        foreach ($names as $node) {
            if (stripos($node->textContent, 'free') !== false) {
                return true;
            }
        }

        return false;
    }

    protected function extractAvatarUrl(array $jsonLd): ?string
    {
        $image = $jsonLd['image'] ?? null;
        if (is_array($image)) {
            $first = $image[0] ?? null;

            return is_string($first) ? $first : null;
        }

        return is_string($image) ? $image : null;
    }

    protected function extractFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    protected function extractInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $value));

        return $trimmed === '' ? null : $trimmed;
    }
}
