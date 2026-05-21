<?php

namespace App\Models\Concerns;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant-scoping trait — defined in Phase 1, applied in Phase 2+.
 *
 * Models that `use BelongsToAccount`:
 *   - Get a global scope that filters by Auth::user()?->currentAccount?->id
 *     when a tenant context is present.
 *   - Get an account() BelongsTo relation.
 *
 * Disable globally with BelongsToAccount::disable() (artisan commands, jobs,
 * audits). Re-enable with BelongsToAccount::enable().
 */
trait BelongsToAccount
{
    protected static bool $belongsToAccountEnabled = true;

    public static function bootBelongsToAccount(): void
    {
        static::addGlobalScope('account', function (Builder $builder) {
            if (! static::$belongsToAccountEnabled) {
                return;
            }
            $accountId = Auth::user()?->currentAccount?->id;
            if ($accountId !== null) {
                $builder->where($builder->getModel()->getTable().'.account_id', $accountId);
            }
        });

        static::creating(function ($model) {
            if (! static::$belongsToAccountEnabled) {
                return;
            }
            if (empty($model->account_id)) {
                $accountId = Auth::user()?->currentAccount?->id;
                if ($accountId !== null) {
                    $model->account_id = $accountId;
                }
            }
        });
    }

    public static function disable(): void
    {
        static::$belongsToAccountEnabled = false;
    }

    public static function enable(): void
    {
        static::$belongsToAccountEnabled = true;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
