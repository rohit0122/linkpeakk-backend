<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * NOTE: razorpay_plan_id and billing_interval are legacy fields from the old subscription model.
     * They are kept in the schema for backward compatibility but not used in the one-time payment flow.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'DEMO',
                'slug' => 'demo',
                'price' => 0.00,
                'currency' => 'USD',
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
                'price' => 0.00,
                'currency' => 'USD',
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
                'price' => 9.00,
                'currency' => 'USD',
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
                'price' => 49.00,
                'currency' => 'USD',
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
