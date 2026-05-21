<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapingJob extends Model
{
    use HasFactory;

    protected $table = 'scraping_jobs';

    protected $fillable = [
        'target_type',
        'target_id',
        'url',
        'status',
        'attempts',
        'last_error',
        'scheduled_for',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'scheduled_for' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
