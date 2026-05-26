<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasUnlisting
{
    public static function bootHasUnlisting(): void
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->whereNull($builder->getModel()->getTable().'.unlisted_at');
        });
    }

    public function scopeWithUnlisted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    public function unlist(): void { $this->update(['unlisted_at' => now()]); }
    public function relist(): void { $this->update(['unlisted_at' => null]); }
    public function isUnlisted(): bool { return $this->unlisted_at !== null; }
}
