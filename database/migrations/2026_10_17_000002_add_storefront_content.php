<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->bigInteger('sale_price_minor')->nullable();
            $table->timestampTz('sale_starts_at')->nullable();
            $table->timestampTz('sale_ends_at')->nullable();
        });
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_valid_sale CHECK ((sale_price_minor IS NULL AND sale_starts_at IS NULL AND sale_ends_at IS NULL) OR (sale_price_minor IS NOT NULL AND sale_starts_at IS NOT NULL AND sale_ends_at IS NOT NULL AND sale_price_minor >= 0 AND sale_price_minor < price_minor AND sale_ends_at > sale_starts_at))');
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'position', 'id']);
        });
        Schema::create('storefront_settings', function (Blueprint $table) {
            $table->id();
            $table->json('content');
            $table->timestamps();
        });
        Schema::create('store_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('title_en');
            $table->string('title_ar')->nullable();
            $table->text('excerpt_en')->nullable();
            $table->text('excerpt_ar')->nullable();
            $table->text('body_en');
            $table->text('body_ar')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('published')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();
            $table->index(['published', 'published_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_posts');
        Schema::dropIfExists('storefront_settings');
        Schema::dropIfExists('product_images');
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_valid_sale');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['sale_price_minor', 'sale_starts_at', 'sale_ends_at']));
    }
};
