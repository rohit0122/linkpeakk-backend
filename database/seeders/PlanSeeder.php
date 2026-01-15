<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * IMPORTANT: Replace 'plan_S3Si6GlKBSnioK' and 'plan_S3SieAJbKmSdPh'
     * with real Razorpay Plan IDs from your dashboard for the subscription system to work.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'DEMO',
                'slug' => 'demo',
                'razorpay_plan_id' => null,
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 0,
                'is_active' => true,
                'features' => [
                    'links' => 5,
                    'pages' => 1,
                    'allowedTemplates' => ['classic'],
                    'themes' => 'ALL',
                    'analytics' => 7,
                    'customQR' => false,
                    'seo' => false,
                    'removeWatermark' => false,
                ],
            ],
            [
                'name' => 'FREE',
                'slug' => 'free',
                'razorpay_plan_id' => null,
                'price' => 0.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 0,
                'is_active' => true,
                'features' => [
                    'links' => 5,
                    'pages' => 1,
                    'allowedTemplates' => ['classic'],
                    'themes' => ['light', 'dark'],
                    'analytics' => 7,
                    'customQR' => false,
                    'seo' => false,
                    'removeWatermark' => false,
                ],
            ],
            [
                'name' => 'PRO',
                'slug' => 'pro',
                'razorpay_plan_id' => 'plan_S3Si6GlKBSnioK',
                'price' => 9.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 7,
                'is_active' => true,
                'features' => [
                    'links' => 1000,
                    'pages' => 1,
                    'allowedTemplates' => ['classic', 'bento', 'hero', 'influencer', 'sleek', 'minimalist', 'glassmorphism', 'stack'],
                    'themes' => ['light', 'dark', 'midnight', 'aurora', 'cyberglow', 'hyperpop', 'zenstone', 'matcha', 'nebula'],
                    'analytics' => 90,
                    'customQR' => true,
                    'seo' => true,
                    'removeWatermark' => true,
                ],
            ],
            [
                'name' => 'AGENCY',
                'slug' => 'agency',
                'razorpay_plan_id' => 'plan_S3SieAJbKmSdPh',
                'price' => 49.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 7,
                'is_active' => true,
                'features' => [
                    'links' => 1000,
                    'pages' => 10,
                    'allowedTemplates' => 'ALL',
                    'themes' => 'ALL',
                    'analytics' => 9999,
                    'customQR' => true,
                    'seo' => true,
                    'removeWatermark' => true,
                    'customBranding' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
