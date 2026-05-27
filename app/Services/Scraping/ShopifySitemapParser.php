<?php

namespace App\Services\Scraping;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ShopifySitemapParser
{
    public function fetch(): string
    {
        $url = config('scraping.discovery.sitemap_url');

        $agents = config('scraping.user_agents');
        $userAgent = $agents[array_rand($agents)];

        $response = Http::withHeaders([
            'User-Agent' => $userAgent,
            'Accept' => 'application/xml,text/xml',
        ])->timeout(60)->get($url);

        $response->throw();

        return $response->body();
    }

    /** @return Collection<int, string> */
    public function extractHandles(string $xml): Collection
    {
        $doc = new \SimpleXMLElement($xml);
        $doc->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $urls = $doc->xpath('//s:url/s:loc');

        return collect($urls)
            ->map(fn (\SimpleXMLElement $loc) => (string) $loc)
            ->filter(fn (string $url) => preg_match('#^https://apps\.shopify\.com/([a-z0-9][a-z0-9\-]*)$#', $url))
            ->map(fn (string $url) => basename(parse_url($url, PHP_URL_PATH)))
            ->values();
    }
}
