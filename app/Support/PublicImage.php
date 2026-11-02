<?php

namespace App\Support;

/**
 * Helpers for images stored under public/images or the public disk
 * (storage/app/public, exposed as /storage/...).
 *
 * A stored path can point at a photo that was never uploaded (or was deleted
 * during a redeploy), so views use exists() before rendering a gallery and
 * fall back to the branded placeholder instead of a broken slide.
 */
final class PublicImage
{
    public static function exists(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        // Remote URLs (CDN, R2) are trusted; the browser reports failures.
        if (preg_match('#^https?://#i', $path)) {
            return true;
        }

        $relative = ltrim($path, '/');

        if (str_starts_with($relative, 'images/')) {
            return is_file(public_path($relative));
        }

        if (str_starts_with($relative, 'storage/')) {
            return is_file(storage_path('app/public/' . substr($relative, strlen('storage/'))));
        }

        // Paths stored without a "/storage/" prefix (e.g. "venues/4/x.jpg").
        if (str_starts_with($relative, 'venues/') || str_starts_with($relative, 'packages/')) {
            return is_file(storage_path('app/public/' . $relative));
        }

        return true;
    }

    public static function url(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
