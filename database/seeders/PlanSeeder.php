<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
                'razorpay_plan_id' => 'plan_pro_id_placeholder',
                'price' => 9.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'trial_days' => 7,
                'is_active' => true,
                'features' => [
                    'links' => 1000, 
                    'pages' => 1, 
                    'allowedTemplates' => ['classic', 'bento', 'hero', 'influencer', 'sleek'], 
                    'themes' => ['light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'retro', 'cyberpunk', 'valentine', 'coffee'], 
                    'analytics' => 90,
                    'customQR' => true,
                    'seo' => true,
                    'removeWatermark' => true,
                ],
            ],
            [
                'name' => 'AGENCY',
                'slug' => 'agency',
                'razorpay_plan_id' => 'plan_agency_id_placeholder',
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
