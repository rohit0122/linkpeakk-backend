<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Seed Users
        $commonPassword = Hash::make('password');
        
        $planMap = DB::table('plans')->pluck('id', 'slug')->toArray();

        $testUsers = [
            [
                'name' => 'Free User',
                'email' => 'free@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['free'] ?? null,
                'plan_expires_at' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro User',
                'email' => 'pro@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['pro'] ?? null,
                'plan_expires_at' => now()->addDays(30),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agency User',
                'email' => 'agency@yopmail.com',
                'password' => $commonPassword,
                'role' => 'agency',
                'plan_id' => $planMap['agency'] ?? null,
                'plan_expires_at' => now()->addDays(30),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Downgrade Simulation',
                'email' => 'downgrade@yopmail.com',
                'password' => $commonPassword,
                'role' => 'agency',
                'plan_id' => $planMap['agency'] ?? null,
                'plan_expires_at' => now()->addDays(20),
                'pending_plan_id' => $planMap['pro'] ?? null, // Will become Pro after 20 days
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Upgrade Simulation',
                'email' => 'upgrade@yopmail.com',
                'password' => $commonPassword,
                'role' => 'user',
                'plan_id' => $planMap['agency'] ?? null, // Was Pro, but upgraded immediately
                'plan_expires_at' => now()->addDays(30),
                'pending_plan_id' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Expiry Simulation',
                'email' => 'expiry_test@yopmail.com',
                'password' => $commonPassword,
                'role' => 'agency',
                'plan_id' => $planMap['agency'] ?? null,
                'plan_expires_at' => now()->subDay(), // Expired yesterday
                'pending_plan_id' => $planMap['pro'] ?? null, // Waiting for Cron to switch to Pro
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($testUsers as $user) {
            if (DB::table('users')->where('email', $user['email'])->doesntExist()) {
                DB::table('users')->insert($user);
            }
        }

        // 2. Seed Bio Pages and Links for users
        $users = DB::table('users')->whereIn('email', array_column($testUsers, 'email'))->get();

        foreach ($users as $user) {
            // Check current effective plan (agency or not)
            $isAgency = ($user->role === 'agency' || str_contains($user->email, 'agency') || str_contains($user->email, 'upgrade') || str_contains($user->email, 'downgrade') || str_contains($user->email, 'expiry'));
            $pageCount = $isAgency ? 5 : 2;

            for ($i = 1; $i <= $pageCount; $i++) {
                $slug = str_replace('@yopmail.com', '', $user->email)."-page-{$i}";
                $pageId = DB::table('bio_pages')->where('slug', $slug)->value('id');

                if (! $pageId) {
                    $pageId = DB::table('bio_pages')->insertGetId([
                        'user_id' => $user->id,
                        'slug' => $slug,
                        'title' => "My Page {$i}",
                        'bio' => "This is page {$i} for test user ".$user->email,
                        'template' => 'sleek',
                        'theme' => 'midnight',
                        'is_active' => true,
                        'created_at' => now()->subMinutes($i * 10),
                        'updated_at' => now(),
                    ]);
                }

                for ($j = 1; $j <= 3; $j++) {
                    $linkTitle = "Favorite Link {$j}";
                    if (DB::table('links')->where('bio_page_id', $pageId)->where('title', $linkTitle)->doesntExist()) {
                        DB::table('links')->insert([
                            'user_id' => $user->id,
                            'bio_page_id' => $pageId,
                            'title' => $linkTitle,
                            'url' => 'https://google.com',
                            'is_active' => true,
                            'order' => $j,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        echo "REALISTIC test users and simulation scenarios seeded successfully.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $emails = [
            'free@yopmail.com',
            'pro@yopmail.com',
            'agency@yopmail.com',
            'upgrade@yopmail.com',
            'downgrade@yopmail.com',
            'expiry_test@yopmail.com',
        ];

        $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');

        DB::table('links')->whereIn('user_id', $userIds)->delete();
        DB::table('bio_pages')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();
    }
};
