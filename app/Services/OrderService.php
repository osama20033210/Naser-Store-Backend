<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderService
{
    public function __construct(private InventoryService $inventory, private Pricing $pricing) {}

    /** Deduct stock immediately. Locks are acquired in product ID order to reduce deadlocks. */
    public function create(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = null;
            if (($data['channel'] ?? 'staff') === 'staff' && isset($data['checkout_key'])) {
                // The same intent has the same customer; match the storefront's lock order.
                $customer = Customer::query()->lockForUpdate()->findOrFail($data['customer_id']);
                $data['checkout_fingerprint'] = hash('sha256', json_encode([
                    'customer_id' => (int) $data['customer_id'],
                    'items' => collect($data['items'])->sortBy('product_id')->map(fn ($item) => ['product_id' => (int) $item['product_id'], 'quantity' => (int) $item['quantity']])->values()->all(),
                    'discount_minor' => (int) ($data['discount_minor'] ?? 0),
                    'notes' => $data['notes'] ?? null,
                ], JSON_THROW_ON_ERROR));
                $existing = Order::query()->where('created_by', $userId)->where('checkout_key', $data['checkout_key'])->first();
                if ($existing) {
                    abort_unless($existing->channel === 'staff' && is_string($existing->checkout_fingerprint) && hash_equals($existing->checkout_fingerprint, $data['checkout_fingerprint']), 409, 'This checkout key was already used for a different request.');

                    return $existing->load(['customer', 'items']);
                }
            }
            $customer ??= Customer::findOrFail($data['customer_id']);
            $items = collect($data['items'])->sortBy('product_id');
            $products = Product::query()->whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            $snapshots = [];
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (! $product || ! $product->active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => 'A product is unavailable or has insufficient stock.']);
                }
                $line = $product->price_minor * $item['quantity'];
                $subtotal += $line;
                $snapshots[] = [
                    'product_id' => $product->id, 'sku' => $product->sku, 'name' => $product->name,
                    'quantity' => $item['quantity'], 'unit_price_minor' => $product->price_minor, 'subtotal_minor' => $line,
                ];
            }
            $discount = (int) ($data['discount_minor'] ?? 0);
            $order = Order::create([
                'number' => (string) Str::uuid(), 'customer_id' => $customer->id, 'created_by' => $userId,
                'status' => OrderStatus::Pending, 'subtotal_minor' => $subtotal, 'discount_minor' => $discount,
                'total_minor' => $this->pricing->total($subtotal, $discount), 'notes' => $data['notes'] ?? null,
                'channel' => $data['channel'] ?? 'staff',
                'payment_method' => $data['payment_method'] ?? null, 'payment_status' => $data['payment_status'] ?? null,
                'shipping_name' => $data['shipping_name'] ?? null, 'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'checkout_key' => $data['checkout_key'] ?? null, 'checkout_fingerprint' => $data['checkout_fingerprint'] ?? null,
            ]);
            $order->items()->createMany($snapshots);
            foreach ($items as $item) {
                $this->inventory->move($products[$item['product_id']], -$item['quantity'], 'order_created', $userId, $order->id);
            }
            $this->notify($order, $customer->email);

            return $order->load(['customer', 'items']);
        }, 3);
    }

    /** Recheck state under lock; only pending/confirmed cancellation restores stock once. */
    public function transition(int $orderId, OrderStatus $next, int $userId): Order
    {
        return DB::transaction(function () use ($orderId, $next, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            if (! $order->status->canTransitionTo($next)) {
                throw ValidationException::withMessages(['status' => 'This status transition is not allowed.']);
            }
            if ($next === OrderStatus::Cancelled) {
                $items = $order->items()->orderBy('product_id')->get();
                $products = Product::query()->whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($items as $item) {
                    $this->inventory->move($products[$item->product_id], $item->quantity, 'order_cancelled', $userId, $order->id);
                }
            }
            $order->status = $next;
            $order->save();
            $order->load(['customer', 'items']);
            $this->notify($order, $order->customer->email);

            return $order;
        }, 3);
    }

    /** Account-owned checkout: customer locking serializes retries with the same idempotency key. */
    public function createForCustomer(array $data, User $user): Order
    {
        abort_unless($user->account_type === 'customer', 403);
        $normalized = [
            'items' => collect($data['items'])->sortBy('product_id')->map(fn ($item) => ['product_id' => (int) $item['product_id'], 'quantity' => (int) $item['quantity']])->values()->all(),
            'shipping_name' => trim($data['shipping_name']), 'shipping_phone' => trim($data['shipping_phone']),
            'shipping_address' => trim($data['shipping_address']), 'notes' => trim($data['notes'] ?? ''),
        ];
        $fingerprint = hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($data, $user, $normalized, $fingerprint) {
            $customer = $user->customer()->lockForUpdate()->firstOrFail();
            $existing = Order::query()->where('created_by', $user->id)->where('checkout_key', $data['checkout_key'])->first();
            if ($existing) {
                abort_unless($existing->channel === 'storefront' && is_string($existing->checkout_fingerprint) && hash_equals($existing->checkout_fingerprint, $fingerprint), 409, 'This checkout key was already used for a different request.');

                return $existing->load(['customer', 'items']);
            }

            return $this->create([
                ...$normalized, 'customer_id' => $customer->id, 'discount_minor' => 0,
                'channel' => 'storefront', 'payment_method' => 'cash_on_delivery', 'payment_status' => 'unpaid',
                'checkout_key' => $data['checkout_key'], 'checkout_fingerprint' => $fingerprint,
            ], $user->id);
        }, 3);
    }

    /** Ownership and pending eligibility are rechecked under the order lock. */
    public function cancelForCustomer(int $orderId, User $user): Order
    {
        abort_unless($user->account_type === 'customer', 403);

        return DB::transaction(function () use ($orderId, $user) {
            $order = Order::query()->where('customer_id', $user->customer->id)->lockForUpdate()->findOrFail($orderId);
            if ($order->channel !== 'storefront' || $order->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only a pending storefront order can be cancelled here. Contact the store for help.']);
            }

            return $this->transition($order->id, OrderStatus::Cancelled, $user->id);
        }, 3);
    }

    public function recordCashPayment(int $orderId, int $userId): Order
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            if ($order->payment_method !== 'cash_on_delivery' || $order->status !== OrderStatus::Completed) {
                throw ValidationException::withMessages(['payment_status' => 'Cash can only be recorded for a completed cash-on-delivery order.']);
            }
            if ($order->payment_status !== 'paid') {
                $order->payment_status = 'paid';
                $order->paid_at = now();
                $order->payment_recorded_by = $userId;
                $order->save();
            }

            return $order->load(['customer', 'items']);
        }, 3);
    }

    private function notify(Order $order, string $email): void
    {
        $notification = new OrderUpdated($order->number, $order->status->value, $order->total_minor);
        DB::afterCommit(function () use ($email, $notification) {
            try {
                Notification::route('mail', $email)->notify($notification->afterCommit());
            } catch (Throwable $exception) {
                // A queue/mail outage must not turn an already committed order into an HTTP failure.
                report($exception);
            }
        });
    }
}
