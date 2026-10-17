<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id', 'sku', 'name', 'description', 'price_minor', 'low_stock_threshold', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'price_minor' => 'integer', 'stock' => 'integer', 'sale_price_minor' => 'integer', 'sale_starts_at' => 'immutable_datetime', 'sale_ends_at' => 'immutable_datetime'];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function coverImagePath(): ?string
    {
        return $this->image_path ?? ($this->relationLoaded('images') ? $this->images->first()?->path : null);
    }

    public function sellingPrice(?\Carbon\CarbonInterface $at = null): int
    {
        $at ??= now();
        return $this->sale_price_minor !== null && $this->sale_price_minor >= 0 && $this->sale_price_minor < $this->price_minor
            && $this->sale_starts_at !== null && $this->sale_ends_at !== null
            && $this->sale_starts_at->lessThanOrEqualTo($at) && $this->sale_ends_at->greaterThan($at)
                ? $this->sale_price_minor : $this->price_minor;
    }

    /** Static SQL only; timestamps are always supplied as bound parameters. */
    public static function sellingPriceExpression(): string
    {
        return 'CASE WHEN sale_price_minor >= 0 AND sale_price_minor < price_minor AND sale_starts_at <= ? AND sale_ends_at > ? THEN sale_price_minor ELSE price_minor END';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
