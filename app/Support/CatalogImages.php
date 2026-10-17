<?php

namespace App\Support;

final class CatalogImages
{
    public static function product(string $sku, string $name): ?array
    {
        $entry = config('catalog_images.products', [])[$sku] ?? null;

        return $entry && $entry['name'] === $name ? self::asset($entry['file']) : null;
    }

    public static function category(string $name): ?array
    {
        $file = config('catalog_images.categories', [])[$name] ?? null;

        return $file ? self::asset($file) : null;
    }

    private static function asset(string $file): ?array
    {
        $credit = config('catalog_images.assets', [])[$file] ?? null;

        return $credit ? ['url' => '/api/v1/store/catalog-images/'.$file, 'credit' => $credit] : null;
    }
}
