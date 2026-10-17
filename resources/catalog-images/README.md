# Catalog photography

Bundled illustrative photography for the current catalog; not photographs of the exact goods for sale. `sources.json` records the original URL, photographer (when supplied), download URL and license for each JPEG. All images were resized to fit within 960 x 960 pixels, EXIF orientation was applied, metadata removed and JPEG compression applied. Creative Commons images retain their linked license; public product details and the primary-image editor display credit, license and modification notices.

`config/catalog_images.php` explicitly matches product SKU AND name, and category name. Uploaded primary photos and loaded gallery photos take precedence. Unlisted products keep the existing empty-image state; upload their actual photos through product management. No image paths or catalog data were written to the database. No seeder or migration is required. Refresh the Laravel configuration cache when deploying new configuration, if caching is enabled.

The public bundled-image route only serves allowlisted JPEG files. Its explicit public caching and security headers are separate from the private/no-store headers used by authenticated API responses and user uploads.
