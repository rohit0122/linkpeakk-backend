<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\BioPage;
use App\Models\Link;
use App\Models\Analytics;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Demo Users...');

        // 1. Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@linkpeakk.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Free User
        $freeUser = User::firstOrCreate(
            ['email' => 'free@linkpeakk.com'],
            [
                'name' => 'Free User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        // Create Free Subscription (if logic requires it, otherwise they just default to free features)
        // Usually systems have a default free plan or no subscription record implies free. 
        // Let's ensure they have a record if your logic expects it, otherwise verify logic.
        // Assuming Logic: No subscription record = fallback to free limits, OR explicit free record.
        // Let's create a subscription with status 'active' but plan_id for FREE just in case.
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
             Subscription::firstOrCreate(
                ['user_id' => $freeUser->id],
                [
                    'plan_id' => $freePlan->id,
                    'razorpay_subscription_id' => 'sub_free_' . Str::random(10),
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => null, // Never expires
                ]
            );
        }
        
        $this->createBioPage($freeUser, 'free-page', 'Free User Page', 'classic', 'light');
        $this->createTickets($freeUser);

        // 3. Create Pro User
        $proUser = User::firstOrCreate(
            ['email' => 'pro@linkpeakk.com'],
            [
                'name' => 'Pro User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        $proPlan = Plan::where('slug', 'pro')->first();
        if ($proPlan) {
            Subscription::firstOrCreate(
                ['user_id' => $proUser->id],
                [
                    'plan_id' => $proPlan->id,
                    'razorpay_subscription_id' => 'sub_pro_' . Str::random(10),
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                ]
            );
        }
        $this->createBioPage($proUser, 'pro-page', 'Pro User Creative', 'hero', 'dark', true);
        $this->createTickets($proUser);


        // 4. Create Agency User
        $agencyUser = User::firstOrCreate(
            ['email' => 'agency@linkpeakk.com'],
            [
                'name' => 'Agency User',
                'password' => Hash::make('password'),
                'role' => 'agency',
                'email_verified_at' => now(),
            ]
        );
        $agencyPlan = Plan::where('slug', 'agency')->first();
        if ($agencyPlan) {
            Subscription::firstOrCreate(
                ['user_id' => $agencyUser->id],
                [
                    'plan_id' => $agencyPlan->id,
                    'razorpay_subscription_id' => 'sub_agency_' . Str::random(10),
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addYear(),
                ]
            );
        }
        
        // Agency has multiple pages
        $this->createBioPage($agencyUser, 'agency-main', 'Agency Main', 'modern', 'dark', true);
        $this->createBioPage($agencyUser, 'agency-client-1', 'Client One', 'glass', 'custom', true);
        $this->createBioPage($agencyUser, 'agency-client-2', 'Client Two', 'minimal', 'light', true);
        $this->createTickets($agencyUser);

        $this->command->info('Demo Data Seeding Completed!');
        $this->command->info('Users: admin@linkpeakk.com, free@linkpeakk.com, pro@linkpeakk.com, agency@linkpeakk.com');
        $this->command->info('Password for all: password');
    }

    private function createBioPage($user, $slug, $title, $template, $theme, $generateAnalytics = false)
    {
        $page = BioPage::firstOrCreate(
            ['slug' => $slug],
            [
                'user_id' => $user->id,
                'title' => $title,
                'bio' => 'Welcome to my ' . $title . '. This is a demo bio.',
                'template' => $template,
                'theme' => $theme,
                'is_active' => true,
                'social_links' => [
                    'twitter' => 'https://twitter.com/linkpeakk',
                    'instagram' => 'https://instagram.com/linkpeakk',
                    'linkedin' => 'https://linkedin.com/company/linkpeakk'
                ],
                'seo' => [
                    'title' => $title . ' - LinkPeakk',
                    'description' => 'Check out my links on LinkPeakk'
                ]
            ]
        );

        // Create Links
        $links = [
            ['title' => 'My Website', 'url' => 'https://example.com', 'icon' => '🌐'],
            ['title' => 'Latest Blog Post', 'url' => 'https://example.com/blog', 'icon' => '📝'],
            ['title' => 'YouTube Channel', 'url' => 'https://youtube.com', 'icon' => '📺'],
            ['title' => 'Store', 'url' => 'https://example.com/store', 'icon' => '🛍️'],
            ['title' => 'Contact Me', 'url' => 'mailto:contact@example.com', 'icon' => '📧'],
        ];

        foreach ($links as $index => $linkData) {
            $link = Link::firstOrCreate(
                ['bio_page_id' => $page->id, 'url' => $linkData['url']],
                [
                    'user_id' => $user->id,
                    'title' => $linkData['title'],
                    'icon' => $linkData['icon'],
                    'is_active' => true,
                    'order' => $index,
                ]
            );

            if ($generateAnalytics) {
                $this->generateAnalytics($page, $link);
            }
        }
        
        if ($generateAnalytics) {
             $this->generatePageAnalytics($page);
        }
    }

    private function generateAnalytics($page, $link)
    {
        // Generate last 30 days of data
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            
            // Random clicks
            $clicks = rand(0, 50);
            $uniqueClicks = floor($clicks * 0.7); // 70% unique roughly
            
            if ($clicks > 0) {
                Analytics::updateOrCreate(
                    ['bio_page_id' => $page->id, 'link_id' => $link->id, 'type' => 'click', 'date' => $date],
                    ['count' => $clicks, 'unique_count' => $uniqueClicks]
                );
                
                // Update total on Link model (approximate for demo)
                $link->increment('clicks', $clicks);
                $link->increment('unique_clicks', $uniqueClicks);
            }
        }
    }

    private function generatePageAnalytics($page)
    {
         // Generate last 30 days of data
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            
            // Page Views need to strictly be higher than total clicks usually, but random is fine for demo
            $views = rand(50, 200);
            $uniqueViews = floor($views * 0.6); 
            
            if ($views > 0) {
                Analytics::updateOrCreate(
                    ['bio_page_id' => $page->id, 'link_id' => null, 'type' => 'view', 'date' => $date],
                    ['count' => $views, 'unique_count' => $uniqueViews]
                );
                
                 // Update total on Page model
                $page->increment('views', $views);
                $page->increment('unique_views', $uniqueViews);
            }
        }
    }

    private function createTickets($user)
    {
        $tickets = [
            [
                'subject' => 'Payment Issue',
                'message' => 'I was charged twice for my subscription. Please help!',
                'status' => 'open',
                'priority' => 'high',
                'category' => 'Billing',
            ],
            [
                'subject' => 'Template customization',
                'message' => 'How can I change the font color of my bento template?',
                'status' => 'pending',
                'priority' => 'medium',
                'category' => 'Technical',
            ],
            [
                'subject' => 'New Feature Request',
                'message' => 'I would love to see a Spotify integration!',
                'status' => 'resolved',
                'priority' => 'low',
                'category' => 'General',
            ],
        ];

        foreach ($tickets as $ticketData) {
            Ticket::create(array_merge($ticketData, ['user_id' => $user->id]));
        }
    }
}
