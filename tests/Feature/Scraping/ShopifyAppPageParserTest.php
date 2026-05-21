<?php

use App\Services\Scraping\ShopifyAppPageParser;

it('parses name, developer, rating, category from a real Klaviyo fixture', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));

    $parsed = (new ShopifyAppPageParser())->parse($html);

    expect($parsed->name)->toBe('Klaviyo: Email Marketing & SMS');
    expect($parsed->developerName)->toBe('Klaviyo');
    expect($parsed->developerUrl)->toContain('/partners/klaviyo');
    expect($parsed->categoryName)->toBe('Email marketing');
    expect($parsed->description)->toContain('Klaviyo is a leading email and sms');
    expect($parsed->averageRating)->toBe(4.6);
    expect($parsed->totalReviews)->toBe(2766);
    expect($parsed->avatarUrl)->toStartWith('https://cdn.shopify.com/app-store/listing_images/');
    expect($parsed->pricingHasFree)->toBeTrue();
    expect($parsed->pricingRaw)->not->toBeNull();
    expect($parsed->pricingRaw)->toContain('Free to install');
});

it('returns nulls for an empty or invalid HTML body', function () {
    $parsed = (new ShopifyAppPageParser())->parse('<html><body>nothing here</body></html>');

    expect($parsed->name)->toBeNull();
    expect($parsed->averageRating)->toBeNull();
    expect($parsed->totalReviews)->toBeNull();
    expect($parsed->pricingHasFree)->toBeFalse();
});
