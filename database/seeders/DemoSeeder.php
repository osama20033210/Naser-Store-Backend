<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use LogicException;

class DemoSeeder extends Seeder
{
    /** Explicit local demo data only; preserve edited records and suppress outbound mail. */
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new LogicException('Demo seeding is local-only.');
        }
        $password = env('DEMO_PASSWORD');
        if (! is_string($password) || strlen($password) < 16) {
            throw new LogicException('Provide DEMO_PASSWORD of at least 16 characters.');
        }

        $this->call(RolePermissionSeeder::class);
        $notifications = Notification::getFacadeRoot();
        Notification::fake();
        try {
            DB::transaction(function () use ($password) {
                $staff = [];
                foreach (['Admin', 'Manager', 'Staff'] as $role) {
                    $user = $this->account(strtolower($role).'@example.test', $role.' Demo', 'staff', $password);
                    if ($user->wasRecentlyCreated) {
                        $user->assignRole($role);
                    } elseif (! $user->hasRole($role)) {
                        throw new LogicException('An existing demo staff email has different roles; no account was reassigned.');
                    }
                    $staff[] = $user;
                }
                $customers = $this->customers($password);
                $products = $this->products($staff[0]);
                $this->orders($customers, $products, $staff);
            });
        } finally {
            Notification::swap($notifications);
        }
        $this->command?->info('Demo data ready: 3 staff accounts, 4 customer accounts, 8 customers, 6 categories, 24 products and 40 orders. Existing demo records were preserved.');
    }

    private function account(string $email, string $name, string $type, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->fill(['name' => $name, 'password' => $password]);
            $user->account_type = $type;
            $user->save();
        }
        if ($user->account_type !== $type) {
            throw new LogicException('A demo email belongs to another account type; no account was converted.');
        }

        return $user;
    }

    private function customers(string $password): array
    {
        $customers = [];
        foreach (['Demo Customer One', 'Demo Customer Two', 'Demo Customer Three', 'Demo Customer Four', 'Demo Studio', 'Demo Office', 'Demo Workshop', 'Demo Retail'] as $index => $name) {
            $email = 'demo.customer'.($index + 1).'@example.test';
            $user = $index < 4 ? $this->account($email, $name, 'customer', $password) : null;
            $customer = Customer::firstOrNew(['email' => $email]);
            if (! $customer->exists) {
                $customer->fill(['name' => $name, 'phone' => '+1 202-555-'.sprintf('%04d', 100 + $index), 'address' => ($index + 1).' Demo Street, Sample City']);
                $customer->user_id = $user?->id;
                $customer->save();
            } elseif (($customer->user_id === null ? null : (int) $customer->user_id) !== $user?->id) {
                throw new LogicException('A demo customer email has an existing history/link; no customer account was linked.');
            }
            $customers[] = $customer;
        }

        return $customers;
    }

    private function products(User $admin): array
    {
        $catalog = [
            'Workspace' => [['Adjustable desk', 42500], ['Office chair', 28900], ['Desk organizer', 2400], ['Monitor arm', 6900]],
            'Lighting' => [['Task lamp', 7900], ['Floor lamp', 11900], ['Reading light', 3500], ['Wall light', 5900]],
            'Accessories' => [['Desk mat', 2900], ['Laptop stand', 4900], ['Cable organizer', 1200], ['Storage tray', 1800]],
            'Storage' => [['Drawer cabinet', 14900], ['Book shelf', 17900], ['Storage box', 2200], ['Wall shelf', 3900]],
            'Stationery' => [['Notebook set', 1600], ['Pen set', 900], ['Weekly planner', 1400], ['Document folder', 700]],
            'Home office' => [['Footrest', 4500], ['Seat cushion', 3200], ['Acoustic panel', 8900], ['Coat stand', 9900]],
        ];
        $products = [];
        $inventory = app(InventoryService::class);
        $startedAt = now()->subDays(30)->startOfDay()->addHours(8);
        foreach ($catalog as $categoryName => $rows) {
            $category = Category::firstOrCreate(['name' => 'Demo / '.$categoryName], ['description' => 'Local demonstration catalog: '.$categoryName]);
            foreach ($rows as [$name, $price]) {
                $index = count($products);
                $product = Product::firstOrCreate(['sku' => sprintf('DEMO-P%02d', $index + 1)], [
                    'name' => $name.' (Demo)',
                    'description' => 'Demonstration item for reviewing the store and product management. Not a live product listing.',
                    'category_id' => $category->id,
                    'price_minor' => $price,
                    'active' => $index !== 22,
                    'low_stock_threshold' => 5,
                ]);
                if ($product->wasRecentlyCreated) {
                    $stock = $index < 18 ? 60 + $index * 3 : [0, 2, 4, 5, 40, 20][$index - 18];
                    Carbon::withTestNow($startedAt, function () use ($inventory, $product, $stock, $admin, $index) {
                        if ($stock > 0) {
                            $inventory->adjust($product->id, $stock, 'opening_stock', $admin->id);
                        }
                        if ($index === 0) {
                            $inventory->adjust($product->id, 8, 'manual_adjustment', $admin->id);
                            $inventory->adjust($product->id, -3, 'manual_adjustment', $admin->id);
                        }
                    });
                }
                $products[] = $product;
            }
        }

        return $products;
    }

    /** Stable order IDs make reruns skip the entire creation/transition/stock workflow. */
    private function orders(array $customers, array $products, array $staff): void
    {
        $service = app(OrderService::class);
        $base = now()->subDays(27)->startOfDay()->addHours(9);
        for ($index = 0; $index < 40; $index++) {
            $number = $this->uuid($index + 1);
            if (Order::where('number', $number)->exists()) {
                continue;
            }
            $online = $index % 3 !== 0;
            $customer = $customers[$index % ($online ? 4 : 8)];
            $creator = $online ? User::findOrFail($customer->user_id) : $staff[intdiv($index, 3) % 3];
            $createdAt = $base->copy()->addDays(intdiv($index * 27, 40))->addMinutes(($index % 6) * 35);
            Carbon::withTestNow($createdAt, function () use ($service, $products, $customer, $creator, $staff, $online, $index, $number, $createdAt) {
                $data = [
                    'customer_id' => $customer->id,
                    'items' => [
                        ['product_id' => $products[$index % 18]->id, 'quantity' => 1 + $index % 3],
                        ['product_id' => $products[($index + 5) % 18]->id, 'quantity' => 1],
                    ],
                    'notes' => 'LOCAL DEMO ORDER '.($index + 1).' - synthetic data, not a real purchase.',
                ];
                if ($online) {
                    $order = $service->createForCustomer([
                        ...$data,
                        'checkout_key' => $this->uuid(1000 + $index),
                        'shipping_name' => $customer->name,
                        'shipping_phone' => $customer->phone,
                        'shipping_address' => $customer->address,
                    ], $creator);
                } else {
                    $order = $service->create([...$data, 'discount_minor' => $index % 2 === 0 ? 500 : 0], $creator->id);
                }
                $order->number = $number;
                $order->save();
                $target = [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Completed, OrderStatus::Cancelled][$index % 5];
                $steps = match ($target) {
                    OrderStatus::Pending => [],
                    OrderStatus::Confirmed => [OrderStatus::Confirmed],
                    OrderStatus::Processing => [OrderStatus::Confirmed, OrderStatus::Processing],
                    OrderStatus::Completed => [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Completed],
                    OrderStatus::Cancelled => [OrderStatus::Cancelled],
                };
                foreach ($steps as $step => $status) {
                    Carbon::withTestNow($createdAt->copy()->addHours($step + 1), fn () => $service->transition($order->id, $status, $staff[1]->id));
                }
                if ($online && $target === OrderStatus::Completed && $index % 2 === 0) {
                    Carbon::withTestNow($createdAt->copy()->addHours(4), fn () => $service->recordCashPayment($order->id, $staff[0]->id));
                }
            });
        }
    }

    private function uuid(int $sequence): string
    {
        return 'de000000-0000-4000-8000-'.sprintf('%012d', $sequence);
    }
}
