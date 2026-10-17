<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(int $productId, int $change, string $reason, int $userId): Product
    {
        return DB::transaction(function () use ($productId, $change, $reason, $userId) {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
            $this->move($product, $change, $reason, $userId);

            return $product->load('category');
        }, 3);
    }

    /** Product locking serializes identical retries; the unique key also rejects cross-product reuse. */
    public function adjustOnce(int $productId, int $change, string $reason, int $userId, string $key): Product
    {
        return DB::transaction(function () use ($productId, $change, $reason, $userId, $key) {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
            $existing = InventoryMovement::query()->where('user_id', $userId)->where('adjustment_key', $key)->first();
            if ($existing) {
                abort_unless((int) $existing->product_id === $productId && (int) $existing->quantity_change === $change && $existing->reason === $reason && $existing->order_id === null, 409, 'This adjustment key was already used for a different request.');

                return $product->load('category');
            }
            $this->applyMovement($product, $change, $reason, $userId, null, $key);

            return $product->load('category');
        }, 3);
    }

    /** Caller must hold this product's row lock inside a transaction. */
    public function move(Product $product, int $change, string $reason, int $userId, ?int $orderId = null): void
    {
        $this->applyMovement($product, $change, $reason, $userId, $orderId);
    }

    /** Caller holds the product lock; legacy movements do not require the new optional column. */
    private function applyMovement(Product $product, int $change, string $reason, int $userId, ?int $orderId, ?string $key = null): void
    {
        $stock = $product->stock + $change;
        if ($change === 0 || $stock < 0 || $stock > 2147483647) {
            throw ValidationException::withMessages(['stock' => "Invalid stock change for {$product->sku}."]);
        }
        $product->stock = $stock;
        $product->save();
        InventoryMovement::create([
            'product_id' => $product->id, 'order_id' => $orderId, 'user_id' => $userId,
            'quantity_change' => $change, 'stock_after' => $stock, 'reason' => $reason,
            ...($key === null ? [] : ['adjustment_key' => $key]),
        ]);
    }
}
