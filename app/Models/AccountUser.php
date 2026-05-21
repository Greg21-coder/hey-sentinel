<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AccountUser extends Pivot
{
    use HasFactory;

    protected $table = 'account_user';

    public $incrementing = true;

    protected $fillable = [
        'account_id',
        'user_id',
        'role',
        'invited_by',
        'invitation_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'invitation_accepted_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
