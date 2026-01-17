<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait FormattedResponseTrait
{
    /**
     * Format dates consistently across the API during serialization.
     * jan 2026, 11:32PM format
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Generate absolute URL for images stored in the public disk.
     */
    public function getAbsoluteUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // If it's already an absolute URL, return it
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, 'api/')) {
            return config('app.public_url').'/'.$path;
        }

        // Storage::url now uses LARAVEL_BACKEND_URL via config/filesystems.php
        return Storage::disk('public')->url($path);
    }
}
