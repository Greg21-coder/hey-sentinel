<?php

namespace App\Services\Scraping;

use DateTimeImmutable;

readonly class ParsedReview
{
    public function __construct(
        public string $externalId,
        public string $reviewerName,
        public ?string $reviewerCountry,
        public int $rating,
        public string $reviewText,
        public DateTimeImmutable $publishedAt,
    ) {}
}
