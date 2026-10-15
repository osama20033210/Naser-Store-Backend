<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('price_minor');
            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('number')->unique();
            $table->foreignId('customer_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->index()->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->bigInteger('subtotal_minor');
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('total_minor');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->index()->constrained()->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('name');
            $table->integer('quantity');
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('subtotal_minor');
            $table->unique(['order_id', 'product_id']);
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_change');
            $table->integer('stock_after');
            $table->string('reason');
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_values_check CHECK (price_minor >= 0 AND stock >= 0 AND low_stock_threshold >= 0)');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_values_check CHECK (subtotal_minor >= 0 AND discount_minor >= 0 AND discount_minor <= subtotal_minor AND total_minor = subtotal_minor - discount_minor), ADD CONSTRAINT orders_status_check CHECK (status IN ('pending', 'confirmed', 'processing', 'completed', 'cancelled'))");
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT items_values_check CHECK (quantity > 0 AND unit_price_minor >= 0 AND subtotal_minor = quantity::bigint * unit_price_minor)');
        DB::statement('ALTER TABLE inventory_movements ADD CONSTRAINT movements_values_check CHECK (quantity_change <> 0 AND stock_after >= 0)');
    }

    public function down(): void
    {
        foreach (['inventory_movements', 'order_items', 'orders', 'customers', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
