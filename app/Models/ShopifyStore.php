<?php

namespace App\Models;

use App\Enums\ScrapingStatus;
use App\Models\Concerns\HasUnlisting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShopifyStore extends Model
{
    use HasFactory, HasUnlisting;

    protected $fillable = [
        'domain',
        'store_name',
        'country_code',
        'language_code',
        'platform_tier',
        'estimated_monthly_visits',
        'estimated_monthly_sales_usd',
        'employees_estimate',
        'theme_name',
        'apps_installed_count',
        'storeleads_id',
        'storeleads_payload_hash',
        'storeleads_synced_at',
        'scraping_status',
        'unlisted_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_monthly_visits' => 'integer',
            'estimated_monthly_sales_usd' => 'integer',
            'employees_estimate' => 'integer',
            'apps_installed_count' => 'integer',
            'storeleads_synced_at' => 'datetime',
            'scraping_status' => ScrapingStatus::class,
            'unlisted_at' => 'datetime',
        ];
    }

    public function rawPayload(): HasOne
    {
        return $this->hasOne(ShopifyStoreRawPayload::class, 'shopify_store_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class);
    }
}
