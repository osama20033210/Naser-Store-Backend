<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'sku', 'name', 'quantity', 'unit_price_minor', 'subtotal_minor'];

    protected function casts(): array
    {
        return ['unit_price_minor' => 'integer', 'subtotal_minor' => 'integer', 'quantity' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
