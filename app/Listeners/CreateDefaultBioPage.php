<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateDefaultBioPage
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(\Illuminate\Auth\Events\Verified $event): void
    {
        $user = $event->user;

        // Check if user already has a bio page
        if ($user->bioPages()->exists()) {
            return;
        }

        // Generate a unique slug from the name
        $baseSlug = \Illuminate\Support\Str::slug($user->name);
        $slug = $baseSlug;
        $count = 1;

        while (\App\Models\BioPage::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        // Create default bio page
        \App\Models\BioPage::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'title' => $user->name,
            'bio' => 'Welcome to my bio page!',
            'theme' => 'classic',
            'is_active' => true,
        ]);
    }
}
