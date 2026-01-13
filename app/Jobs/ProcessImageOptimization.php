<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessImageOptimization implements ShouldQueue
{
    use Queueable;

    protected $path;
    protected $model;
    protected $column;
    protected $width;
    protected $height;

    /**
     * Create a new job instance.
     */
    public function __construct($path, $model, $column, $width = 800, $height = 800)
    {
        $this->path = $path;
        $this->model = $model;
        $this->column = $column;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!Storage::disk('public')->exists($this->path)) {
            return;
        }

        $fullPath = storage_path('app/public/' . $this->path);
        if (!file_exists($fullPath)) return;

        $manager = new ImageManager(new Driver());
        $image = $manager->read($fullPath);

        // Optimise and Resize
        $image->scaleDown(width: $this->width, height: $this->height);
        $encoded = $image->toWebp(80);

        // Generate new name
        $newPath = str_replace(pathinfo($this->path, PATHINFO_EXTENSION), 'webp', $this->path);

        // Store new image
        Storage::disk('public')->put($newPath, (string) $encoded);

        // Update Model
        $this->model->update([$this->column => $newPath]);

        // Cleanup old file IF it's different
        if ($this->path !== $newPath) {
            Storage::disk('public')->delete($this->path);
        }
    }
}
