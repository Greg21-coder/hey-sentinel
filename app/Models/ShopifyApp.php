<?php

namespace App\Models;

use App\Enums\ScrapingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopifyApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_app_handle',
        'name',
        'developer_name',
        'developer_url',
        'category_id',
        'description',
        'pricing_raw',
        'pricing_structured',
        'pricing_min_usd',
        'pricing_has_free',
        'avatar_url',
        'average_rating',
        'total_reviews',
        'total_installs_estimate',
        'scraping_status',
        'scraping_error',
        'last_scraped_at',
        'ai_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'pricing_structured' => 'array',
            'pricing_min_usd' => 'decimal:2',
            'pricing_has_free' => 'boolean',
            'average_rating' => 'decimal:2',
            'total_reviews' => 'integer',
            'total_installs_estimate' => 'integer',
            'scraping_status' => ScrapingStatus::class,
            'last_scraped_at' => 'datetime',
            'ai_processed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopifyAppCategory::class, 'category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AiTag::class, 'app_tag', 'app_id', 'tag_id')->withPivot('mention_count');
    }

    public function scopePendingScraping(Builder $query): Builder
    {
        return $query->where('scraping_status', ScrapingStatus::Pending->value);
    }
}
