<?php

namespace App\Models;

use App\Enums\FollowedAppKind;
use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountFollowedApp extends Model
{
    use BelongsToAccount, HasFactory;

    protected $fillable = [
        'account_id',
        'shopify_app_id',
        'kind',
        'followed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'kind' => FollowedAppKind::class,
            'followed_at' => 'datetime',
        ];
    }

    public function shopifyApp(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class);
    }
}
