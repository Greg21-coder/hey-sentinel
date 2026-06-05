<?php

namespace App\Models;

use App\Enums\AiStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StoreReview extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'shopify_app_id',
        'shopify_store_id',
        'reviewer_name',
        'rating',
        'review_text',
        'review_text_hash',
        'language_code',
        'ai_status',
        'ai_sentiment',
        'ai_pain_points_json',
        'ai_processed_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'ai_status' => AiStatus::class,
            'ai_processed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StoreReview $review) {
            if (empty($review->review_text_hash) && ! empty($review->review_text)) {
                $review->review_text_hash = hash('sha256', $review->review_text);
            }
        });
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'shopify_app_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    public function painPoints(): BelongsToMany
    {
        return $this->belongsToMany(AiPainPoint::class, 'review_pain_point', 'review_id', 'pain_point_id')
            ->withPivot(['severity', 'confidence']);
    }

    public function scopePendingAi(Builder $query): Builder
    {
        return $query->where('ai_status', AiStatus::Pending->value);
    }
}
