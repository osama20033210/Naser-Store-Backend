<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /** Serve only public catalog raster files with server-generated filenames. */
    public function __invoke(string $filename)
    {
        abort_unless(preg_match('~^[0-9a-f-]{36}\.(jpg|png|webp)$~D', $filename), 404);
        $disk = Storage::disk('public');
        $path = 'products/'.$filename;
        abort_unless($disk->exists($path), 404);
        $type = match (pathinfo($filename, PATHINFO_EXTENSION)) {
            'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        };

        return response()->file($disk->path($path), [
            'Content-Type' => $type, 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}
