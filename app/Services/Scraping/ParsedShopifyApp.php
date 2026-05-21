<?php

namespace App\Services\Scraping;

readonly class ParsedShopifyApp
{
    public function __construct(
        public ?string $name,
        public ?string $developerName,
        public ?string $developerUrl,
        public ?string $categoryName,
        public ?string $description,
        public ?string $pricingRaw,
        public bool $pricingHasFree,
        public ?string $avatarUrl,
        public ?float $averageRating,
        public ?int $totalReviews,
    ) {}
}
