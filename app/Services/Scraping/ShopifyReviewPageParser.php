<?php

namespace App\Services\Scraping;

use Carbon\Carbon;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

class ShopifyReviewPageParser
{
    public function parse(string $html): ParsedReviewPage
    {
        $crawler = new Crawler($html);

        $reviews = $crawler->filter('div[data-merchant-review][data-review-content-id]')
            ->each(fn (Crawler $node) => $this->parseReview($node))
            ;

        return new ParsedReviewPage(
            reviews: array_values(array_filter($reviews)),
            hasNextPage: $crawler->filter('a[rel="next"]')->count() > 0,
        );
    }

    protected function parseReview(Crawler $node): ?ParsedReview
    {
        $externalId = $node->attr('data-review-content-id');
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $rating = $this->extractRating($node);
        if ($rating === null) {
            return null;
        }

        $reviewText = $this->extractReviewText($node);
        if ($reviewText === '') {
            return null;
        }

        $publishedAt = $this->extractPublishedAt($node);
        if ($publishedAt === null) {
            return null;
        }

        return new ParsedReview(
            externalId: $externalId,
            reviewerName: $this->extractReviewerName($node) ?? 'Unknown',
            reviewerCountry: $this->extractReviewerCountry($node),
            rating: $rating,
            reviewText: $reviewText,
            publishedAt: $publishedAt,
        );
    }

    protected function extractRating(Crawler $node): ?int
    {
        $ratingNode = $node->filter('[aria-label*="out of 5 stars"]')->first();
        if ($ratingNode->count() === 0) {
            return null;
        }

        if (preg_match('/(\d+)\s+out of 5/', $ratingNode->attr('aria-label') ?? '', $m)) {
            return (int) $m[1];
        }

        return null;
    }

    protected function extractReviewText(Crawler $node): string
    {
        $body = $node->filter('div[data-truncate-review]:not([data-reply-id])')->first();
        if ($body->count() === 0) {
            return '';
        }

        $paragraphs = $body->filter('[data-truncate-content-copy] p')
            ->each(fn (Crawler $p) => trim($p->text(null, true)));

        $paragraphs = array_values(array_filter($paragraphs, fn ($p) => $p !== ''));

        if (empty($paragraphs)) {
            return trim(preg_replace('/\s+/u', ' ', $body->text(null, true)));
        }

        return implode("\n\n", $paragraphs);
    }

    protected function extractReviewerName(Crawler $node): ?string
    {
        $span = $node->filter('span[title]')->first();
        if ($span->count() > 0) {
            $name = trim($span->attr('title') ?? '');
            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    protected function extractReviewerCountry(Crawler $node): ?string
    {
        // The reviewer's block looks like:
        //   <div class="tw-order-1 ... tw-text-fg-tertiary tw-text-body-xs">
        //     <div class="tw-text-heading-xs ..."><span title="Store Name">…</span></div>
        //     <div>Country</div>
        //     <div>X years using the app</div>
        //   </div>
        // Walk to the reviewer container via the span[title], then take direct
        // child divs and return the first one that isn't the heading and isn't
        // the time-using-app sentence.
        $titleSpan = $node->filter('span[title]')->first();
        if ($titleSpan->count() === 0) {
            return null;
        }

        $domNode = $titleSpan->getNode(0);
        // span[title] -> tw-text-heading-xs div -> reviewer container
        $reviewerContainer = $domNode?->parentNode?->parentNode;
        if ($reviewerContainer === null) {
            return null;
        }

        foreach ($reviewerContainer->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE || $child->nodeName !== 'div') {
                continue;
            }

            $classAttr = $child instanceof \DOMElement ? $child->getAttribute('class') : '';
            if (str_contains($classAttr, 'tw-text-heading-xs')) {
                continue;
            }

            $text = trim(preg_replace('/\s+/u', ' ', $child->textContent));
            if ($text === '' || preg_match('/(using the app|year|month|day|week|ago)/i', $text)) {
                continue;
            }

            return $text;
        }

        return null;
    }

    protected function extractPublishedAt(Crawler $node): ?DateTimeImmutable
    {
        $candidates = $node->filter('div.tw-text-body-xs')->each(fn (Crawler $d) => trim($d->text(null, true)));

        foreach ($candidates as $text) {
            if (preg_match('/^[A-Z][a-z]+\s+\d{1,2},\s+\d{4}$/', $text)) {
                try {
                    return new DateTimeImmutable(Carbon::parse($text)->toDateTimeString());
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }
}
