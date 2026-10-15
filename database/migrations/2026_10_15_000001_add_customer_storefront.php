<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // All accounts predating the storefront were employee accounts.
            $table->string('account_type', 16)->default('staff');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('channel', 16)->default('staff');
            // Historical payment and delivery details remain unknown.
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 16)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payment_recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('shipping_name')->nullable();
            $table->string('shipping_phone', 40)->nullable();
            $table->text('shipping_address')->nullable();
            $table->uuid('checkout_key')->nullable();
            $table->string('checkout_fingerprint', 64)->nullable();
            $table->unique(['created_by', 'checkout_key']);
        });
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_account_type_check CHECK (account_type IN ('staff', 'customer'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_channel_check CHECK (channel IN ('staff', 'storefront')), ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IS NULL OR payment_method = 'cash_on_delivery'), ADD CONSTRAINT orders_payment_status_check CHECK (payment_status IS NULL OR payment_status IN ('unpaid', 'paid'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_channel_check, DROP CONSTRAINT orders_payment_method_check, DROP CONSTRAINT orders_payment_status_check');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['created_by', 'checkout_key']);
            $table->dropConstrainedForeignId('payment_recorded_by');
            $table->dropColumn(['channel', 'payment_method', 'payment_status', 'paid_at', 'shipping_name', 'shipping_phone', 'shipping_address', 'checkout_key', 'checkout_fingerprint']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_account_type_check');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('account_type'));
    }
};
