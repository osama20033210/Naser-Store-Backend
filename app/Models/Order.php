<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['number', 'customer_id', 'created_by', 'status', 'subtotal_minor', 'discount_minor', 'total_minor', 'notes', 'channel', 'payment_method', 'payment_status', 'shipping_name', 'shipping_phone', 'shipping_address', 'checkout_key', 'checkout_fingerprint'];

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'subtotal_minor' => 'integer', 'discount_minor' => 'integer', 'total_minor' => 'integer', 'paid_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
