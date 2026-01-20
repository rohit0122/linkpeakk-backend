<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateBlobImagesToFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-blob-images-to-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate image blobs from database to filesystem converting to WebP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting image migration...');

        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver);

        // 1. Migrate Users
        $this->info('Migrating User Avatars...');
        \Illuminate\Support\Facades\DB::table('users')
            ->whereNotNull('avatar_url_blob')
            ->orderBy('id')
            ->chunk(100, function ($users) use ($manager) {
                foreach ($users as $user) {
                    try {
                        if (empty($user->avatar_url_blob)) {
                            continue;
                        }

                        // Create image from blob
                        $image = $manager->read($user->avatar_url_blob);

                        // Optimize to WebP
                        $encoded = $image->toWebp(80);

                        $name = \Illuminate\Support\Str::random(40).'.webp';
                        $path = 'avatars/'.$name;

                        // Save file
                        \Illuminate\Support\Facades\Storage::disk('public')->put($path, (string) $encoded);

                        // Update record
                        \Illuminate\Support\Facades\DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'avatar_url' => $path,
                            ]);

                        $this->line("Migrated User ID: {$user->id}");

                    } catch (\Exception $e) {
                        $this->error("Failed to migrate User ID {$user->id}: ".$e->getMessage());
                    }
                }
            });

        // 2. Migrate Bio Pages
        $this->info('Migrating Bio Page Profile Images...');
        \Illuminate\Support\Facades\DB::table('bio_pages')
            ->whereNotNull('profile_image_blob')
            ->orderBy('id')
            ->chunk(100, function ($pages) use ($manager) {
                foreach ($pages as $page) {
                    try {
                        if (empty($page->profile_image_blob)) {
                            continue;
                        }

                        // Create image from blob
                        $image = $manager->read($page->profile_image_blob);

                        // Optimize to WebP
                        $encoded = $image->toWebp(80);

                        $name = \Illuminate\Support\Str::random(40).'.webp';
                        $path = 'pages/'.$name;

                        \Illuminate\Support\Facades\Storage::disk('public')->put($path, (string) $encoded);

                        \Illuminate\Support\Facades\DB::table('bio_pages')
                            ->where('id', $page->id)
                            ->update([
                                'profile_image' => $path,
                            ]);

                        $this->line("Migrated BioPage ID: {$page->id}");

                    } catch (\Exception $e) {
                        $this->error("Failed to migrate BioPage ID {$page->id}: ".$e->getMessage());
                    }
                }
            });

        $this->info('Migration completed successfully.');
    }
}
