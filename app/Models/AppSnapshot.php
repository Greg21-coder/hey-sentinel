<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopify_app_id', 'name', 'developer_name', 'description_hash',
        'pricing_raw', 'pricing_min_usd', 'pricing_has_free',
        'average_rating', 'total_reviews', 'category_name', 'avatar_url', 'snapshot_at',
    ];

    protected function casts(): array
    {
        return [
            'pricing_min_usd' => 'decimal:2',
            'pricing_has_free' => 'boolean',
            'average_rating' => 'decimal:2',
            'total_reviews' => 'integer',
            'snapshot_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'shopify_app_id');
    }
}
