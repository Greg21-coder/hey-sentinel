<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopify_app_id', 'field', 'old_value', 'new_value', 'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'shopify_app_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppChangeNotification::class);
    }
}
