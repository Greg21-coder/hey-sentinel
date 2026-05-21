<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FeatureUsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureUsageLog>
 */
class FeatureUsageLogFactory extends Factory
{
    protected $model = FeatureUsageLog::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'feature_key' => 'saved_searches',
            'delta' => 1,
            'occurred_at' => now(),
        ];
    }

    public function forFeature(string $key, int $delta = 1): static
    {
        return $this->state(fn () => [
            'feature_key' => $key,
            'delta' => $delta,
        ]);
    }
}
