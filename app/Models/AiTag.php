<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AiTag extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name'];

    public function apps(): BelongsToMany
    {
        return $this->belongsToMany(ShopifyApp::class, 'app_tag', 'tag_id', 'app_id')->withPivot('mention_count');
    }
}
