<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppChangeNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'app_change_id', 'account_id', 'read_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function change(): BelongsTo
    {
        return $this->belongsTo(AppChange::class, 'app_change_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
