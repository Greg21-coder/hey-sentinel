<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyStoreRawPayload extends Model
{
    protected $table = 'shopify_stores_raw_payload';

    protected $primaryKey = 'shopify_store_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'shopify_store_id',
        'payload',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }
}
