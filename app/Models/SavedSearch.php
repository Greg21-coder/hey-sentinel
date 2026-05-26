<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use BelongsToAccount, HasFactory;

    protected $fillable = ['account_id', 'user_id', 'name', 'filters', 'notify_on_new', 'last_viewed_at'];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'notify_on_new' => 'boolean',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
