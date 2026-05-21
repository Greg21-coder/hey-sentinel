<?php

use App\Services\Scraping\ShopifyReviewPageParser;

it('parses reviews + pagination from a real Klaviyo reviews fixture', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/reviews-klaviyo-page-1.html'));

    $page = (new ShopifyReviewPageParser())->parse($html);

    expect($page->hasNextPage)->toBeTrue();
    expect(count($page->reviews))->toBeGreaterThanOrEqual(5);

    $first = $page->reviews[0];
    expect($first->externalId)->toBe('2204756');
    expect($first->rating)->toBe(1);
    expect($first->reviewerName)->toBe('North Ones');
    expect($first->reviewerCountry)->toBe('Sweden');
    expect($first->reviewText)->toContain('Extremely unhappy');
    expect($first->publishedAt->format('Y-m-d'))->toBe('2026-05-15');
});

it('returns empty list and hasNextPage=false for blank HTML', function () {
    $page = (new ShopifyReviewPageParser())->parse('<html><body></body></html>');

    expect($page->reviews)->toBeEmpty();
    expect($page->hasNextPage)->toBeFalse();
});
