<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsageLog extends Model
{
    use HasFactory;

    protected $table = 'feature_usage_log';

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'feature_key',
        'delta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
