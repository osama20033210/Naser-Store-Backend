<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePost extends Model
{
    protected $fillable = ['slug', 'title_en', 'title_ar', 'excerpt_en', 'excerpt_ar', 'body_en', 'body_ar', 'product_id', 'published', 'published_at'];

    protected function casts(): array { return ['published' => 'boolean', 'published_at' => 'immutable_datetime']; }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
