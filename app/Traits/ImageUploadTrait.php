<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait ImageUploadTrait
{
    /**
     * Upload and optimize image to WebP format.
     */
    public function uploadImage(UploadedFile $file, string $folder = 'uploads', int $width = null, int $height = null): string
    {
        $manager = new ImageManager(new Driver());
        $name = Str::random(20) . '.webp';
        $path = $folder . '/' . $name;

        $image = $manager->read($file);
        if ($width && $height) {
            $image->cover($width, $height);
        } elseif ($width) {
            $image->scale(width: $width);
        } elseif ($height) {
            $image->scale(height: $height);
        }

        $encoded = $image->toWebp(80);
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }

    /**
     * Optimize image to WebP and return binary string.
     */
    public function optimizeImageToBinary(UploadedFile $file, int $width = null, int $height = null): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);

        if ($width && $height) {
            $image->cover($width, $height);
        } elseif ($width) {
            $image->scale(width: $width);
        } elseif ($height) {
            $image->scale(height: $height);
        }

        return (string) $image->toWebp(80);
    }

    /**
     * Store raw image and queue optimization.
     */
    public function uploadImageAsync($file, $model, $column, $folder = 'uploads', $width = 800, $height = 800): string
    {
        // Store raw file first
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $name = Str::random(20) . '.' . $extension;
        $path = $file->storeAs($folder, $name, 'public');

        // Dispatch background job for optimization
        \App\Jobs\ProcessImageOptimization::dispatch($path, $model, $column, $width, $height);

        return $path;
    }

    /**
     * Delete image from storage.
     *
     * @param string|null $path
     * @return void
     */
    public function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
