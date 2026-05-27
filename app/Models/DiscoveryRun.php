<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoveryRun extends Model
{
    protected $fillable = [
        'source',
        'status',
        'started_at',
        'completed_at',
        'apps_found',
        'apps_new',
        'apps_existing',
        'apps_limit',
        'error_message',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'apps_found' => 'integer',
            'apps_new' => 'integer',
            'apps_existing' => 'integer',
            'apps_limit' => 'integer',
        ];
    }
}
