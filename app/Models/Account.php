<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\BillingCycle;
use App\Enums\FeatureValueType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'status',
        'plan_id',
        'billing_cycle',
        'trial_started_at',
        'free_trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'stripe_customer_id',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'billing_cycle' => BillingCycle::class,
            'trial_started_at' => 'datetime',
            'free_trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
            if (empty($account->slug) && ! empty($account->name)) {
                $account->slug = Str::slug($account->name);
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function isOnTrial(): bool
    {
        return $this->status === AccountStatus::Trial
            && $this->free_trial_ends_at?->isFuture() === true;
    }

    public function trialExpired(): bool
    {
        return $this->status === AccountStatus::Trial
            && $this->free_trial_ends_at?->isPast() === true;
    }

    public function canUse(string $featureKey): bool
    {
        $this->assertKnownFeature($featureKey);

        $feature = $this->plan->feature($featureKey);
        if ($feature === null) {
            return false;
        }

        return match ($feature->value_type) {
            FeatureValueType::Boolean => $feature->feature_value === 'true',
            FeatureValueType::Unlimited => true,
            FeatureValueType::Integer => $this->usageThisPeriod($featureKey) < (int) $feature->feature_value,
            FeatureValueType::String => $feature->feature_value !== '',
        };
    }

    public function usageThisPeriod(string $featureKey): int
    {
        $this->assertKnownFeature($featureKey);

        $start = $this->current_period_starts_at ?? $this->created_at;

        return (int) FeatureUsageLog::query()
            ->where('account_id', $this->id)
            ->where('feature_key', $featureKey)
            ->where('occurred_at', '>=', $start)
            ->sum('delta');
    }

    public function recordUsage(string $featureKey, int $delta = 1): void
    {
        $this->assertKnownFeature($featureKey);

        FeatureUsageLog::create([
            'account_id' => $this->id,
            'feature_key' => $featureKey,
            'delta' => $delta,
            'occurred_at' => now(),
        ]);
    }

    protected function assertKnownFeature(string $featureKey): void
    {
        if (! in_array($featureKey, config('features'), true)) {
            throw new InvalidArgumentException("Unknown feature key: {$featureKey}");
        }
    }
}
