<?php

namespace App\Services\Scraping;

readonly class ParsedReviewPage
{
    /**
     * @param  array<int, ParsedReview>  $reviews
     */
    public function __construct(
        public array $reviews,
        public bool $hasNextPage,
    ) {}
}
