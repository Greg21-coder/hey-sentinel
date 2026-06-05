<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppComparisonSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'mine_shopify_app_id',
        'competitor_ids_hash',
        'competitor_ids',
        'summary',
        'winner_shopify_app_id',
        'winner_reasoning',
        'per_metric_comments',
        'model',
        'prompt_version',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'competitor_ids' => 'array',
            'per_metric_comments' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function mineApp(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'mine_shopify_app_id');
    }

    public function winnerApp(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'winner_shopify_app_id');
    }

    public static function hashFor(array $competitorIds): string
    {
        $sorted = collect($competitorIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        return sha1(implode(',', $sorted));
    }
}
