<?php

use App\Models\AppSnapshot;
use App\Models\ShopifyApp;
use App\Services\Intelligence\SnapshotDiffService;

it('detects field changes between two snapshots', function () {
    $service = new SnapshotDiffService;

    $previous = new AppSnapshot([
        'name' => 'Old Name', 'developer_name' => 'Dev',
        'pricing_raw' => 'Free', 'pricing_min_usd' => 0,
        'pricing_has_free' => true, 'average_rating' => 4.50,
        'total_reviews' => 100, 'category_name' => 'Marketing',
        'description_hash' => 'abc123', 'avatar_url' => null,
    ]);

    $current = new AppSnapshot([
        'name' => 'New Name', 'developer_name' => 'Dev',
        'pricing_raw' => '$20/mo', 'pricing_min_usd' => 20.00,
        'pricing_has_free' => false, 'average_rating' => 4.50,
        'total_reviews' => 110, 'category_name' => 'Marketing',
        'description_hash' => 'abc123', 'avatar_url' => null,
    ]);

    $changes = $service->diff($previous, $current);
    $fields = array_column($changes, 'field');
    expect($fields)->toContain('name');
    expect($fields)->toContain('pricing_raw');
    expect($fields)->toContain('pricing_min_usd');
    expect($fields)->toContain('pricing_has_free');
    expect($fields)->toContain('total_reviews');
    expect($fields)->not->toContain('developer_name');
    expect($fields)->not->toContain('category_name');
    expect($fields)->not->toContain('description_hash');
});

it('ignores rating changes below threshold', function () {
    $service = new SnapshotDiffService;

    $previous = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.50, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $current = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.53, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    expect($service->diff($previous, $current))->toBeEmpty();
});

it('detects rating changes above threshold', function () {
    $service = new SnapshotDiffService;

    $previous = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.50, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $current = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.20, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $fields = array_column($service->diff($previous, $current), 'field');
    expect($fields)->toContain('average_rating');
});

it('creates snapshot from ShopifyApp model', function () {
    $app = ShopifyApp::factory()->create([
        'name' => 'Test App', 'developer_name' => 'Dev Co',
        'description' => 'A test app description', 'pricing_raw' => 'Free',
        'average_rating' => 4.80, 'total_reviews' => 500,
    ]);

    $service = new SnapshotDiffService;
    $snapshot = $service->createSnapshot($app);

    expect($snapshot->shopify_app_id)->toBe($app->id);
    expect($snapshot->name)->toBe('Test App');
    expect($snapshot->description_hash)->toBe(hash('sha256', 'A test app description'));
    expect($snapshot->average_rating)->toBe('4.80');
    expect($snapshot->total_reviews)->toBe(500);
});
