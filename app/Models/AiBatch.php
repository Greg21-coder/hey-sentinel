<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'batch_id',
        'status',
        'request_count',
        'cost_usd_estimate',
        'prompt_version',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_count' => 'integer',
            'cost_usd_estimate' => 'decimal:4',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
