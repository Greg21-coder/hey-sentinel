<?php

namespace App\Models;

use App\Enums\FeatureValueType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'feature_key',
        'feature_value',
        'value_type',
    ];

    protected function casts(): array
    {
        return [
            'value_type' => FeatureValueType::class,
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function castedValue(): bool|int|string|null
    {
        return match ($this->value_type) {
            FeatureValueType::Boolean => $this->feature_value === 'true',
            FeatureValueType::Integer => (int) $this->feature_value,
            FeatureValueType::String => $this->feature_value,
            FeatureValueType::Unlimited => null,
        };
    }
}
