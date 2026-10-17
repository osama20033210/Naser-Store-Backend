<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CatalogImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-z0-9-]+\.jpg$/D', $filename)
            && array_key_exists($filename, config('catalog_images.assets', [])), 404);
        $path = resource_path('catalog-images/'.$filename);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Cache-Control' => 'public, max-age=86400',
        ])->setAutoEtag()->setAutoLastModified();
    }
}
