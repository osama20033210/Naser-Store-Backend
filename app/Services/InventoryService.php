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

    /** Caller must hold this product's row lock inside a transaction. */
    public function move(Product $product, int $change, string $reason, int $userId, ?int $orderId = null): void
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
        ]);
    }
}
