<?php

use App\Services\Scraping\ShopifySitemapParser;

it('extracts only app handles from sitemap XML', function () {
    $xml = file_get_contents(__DIR__ . '/../../Fixtures/Scraping/sitemap-apps-sample.xml');

    $parser = new ShopifySitemapParser;
    $handles = $parser->extractHandles($xml);

    expect($handles)->toHaveCount(3)
        ->and($handles->all())->toBe([
            'klaviyo-email-marketing',
            'loox',
            'judgeme',
        ]);
});

it('returns empty collection for empty XML', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';

    $parser = new ShopifySitemapParser;
    $handles = $parser->extractHandles($xml);

    expect($handles)->toBeEmpty();
});
