<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \LogicException('Demo seeding is local-only.');
        }
        $password = env('DEMO_PASSWORD');
        if (! is_string($password) || strlen($password) < 16) {
            throw new \LogicException('Provide DEMO_PASSWORD of at least 16 characters.');
        }
        $this->call(RolePermissionSeeder::class);
        foreach (['Admin', 'Manager', 'Staff'] as $role) {
            $user = User::firstOrCreate(['email' => strtolower($role).'@example.test'], ['name' => $role.' Demo', 'password' => $password]);
            $user->syncRoles([$role]);
        }
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $category = Category::firstOrCreate(['name' => 'Workspace'], ['description' => 'Everyday workspace essentials']);
        foreach ([['DESK-001', 'Standing desk', 42500, 18], ['CHAIR-001', 'Ergonomic chair', 28900, 4], ['LAMP-001', 'Task lamp', 7900, 32]] as [$sku, $name, $price, $stock]) {
            $product = Product::firstOrCreate(['sku' => $sku], ['name' => $name, 'category_id' => $category->id, 'price_minor' => $price, 'active' => true, 'low_stock_threshold' => 5]);
            if ($product->wasRecentlyCreated) {
                app(InventoryService::class)->adjust($product->id, $stock, 'opening_stock', $admin->id);
            }
        }
        Customer::firstOrCreate(['email' => 'buyer@example.test'], ['name' => 'Example Studio', 'phone' => '+1 202-555-0100', 'address' => '100 Example Street']);
    }
}
