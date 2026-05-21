<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AiPainPoint extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'category'];

    public function reviews(): BelongsToMany
    {
        return $this->belongsToMany(StoreReview::class, 'review_pain_point', 'pain_point_id', 'review_id')
            ->withPivot(['severity', 'confidence']);
    }
}
