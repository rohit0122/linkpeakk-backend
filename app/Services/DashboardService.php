<?php

namespace App\Services;

use App\Models\BioPage;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class DashboardService
{
    /**
     * Get initial dashboard data for the user.
     */
    public function getInitialData(User $user)
    {
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role,
            ],
        ];

        // Admins don't need bio page or subscription info in this context
        if ($user->role === 'admin') {
            return $data;
        }

        // Subscription Info
        $subscription = $user->activeSubscription()
            ->with(['plan' => function ($query) {
                $query->select('id', 'name', 'slug', 'price');
            }])
            ->select('subscriptions.id', 'subscriptions.user_id', 'subscriptions.plan_id', 'subscriptions.status', 'subscriptions.razorpay_subscription_id', 'subscriptions.current_period_end', 'subscriptions.trial_ends_at')
            ->first();

        $data['subscription'] = $this->formatSubscription($user, $subscription);

        // Bio Pages Info
        $bioPages = $user->bioPages()
            ->with(['links' => function ($query) {
                $query->select('id', 'user_id', 'bio_page_id', 'title', 'url', 'icon', 'is_active', 'order', 'clicks', 'unique_clicks', 'created_at', 'updated_at')
                    ->orderBy('order');
            }])
            ->select('id', 'user_id', 'slug', 'title', 'bio', 'theme', 'template', 'profile_image', 'social_links', 'seo', 'branding', 'views', 'unique_views', 'is_active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($bioPages->isEmpty()) {
            $data['bio_page'] = null;
            $data['all_page_ids'] = [];

            return $data;
        }

        $lastPage = $bioPages->first();
        $data['bio_page'] = $this->formatBioPage($lastPage);

        // For Agency users, include all page IDs
        if ($user->role === 'agency') {
            $data['all_page_ids'] = $bioPages
                ->map(fn ($page) => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                ])
                ->values()
                ->toArray();
        } else {
            $data['all_page_ids'] = [
                [
                    'id' => $lastPage->id,
                    'slug' => $lastPage->slug,
                ],
            ];
        }

        return $data;
    }

    /**
     * Format a BioPage for the response.
     */
    public function formatBioPage(BioPage $page)
    {
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'bio' => $page->bio,
            'profile_image' => $page->profile_image,
            'template' => $page->template,
            'theme' => $page->theme,
            'social_links' => $page->social_links,
            'branding' => $page->branding,
            'total_views' => $page->total_views,
            'unique_views' => $page->unique_views,
            'total_active_links' => $page->links->where('is_active', true)->count(),
            'is_active' => (bool) $page->is_active,
            'seo' => $page->seo,
            'links' => $page->links()
                ->select('id', 'user_id', 'bio_page_id', 'title', 'url', 'icon', 'is_active', 'order', 'clicks', 'unique_clicks', 'created_at', 'updated_at')
                ->orderBy('order')
                ->get(),
        ];
    }

    /**
     * Format a Subscription for the response.
     */
    public function formatSubscription(User $user, ?Subscription $subscription)
    {
        if (! $subscription) {
            $freePlan = Plan::where('slug', 'free')->select('id', 'name', 'slug')->first();

            return [
                'status' => 'free',
                'plan_name' => $freePlan ? $freePlan->name : 'FREE',
                'expiry_date' => 'Never',
                'is_trial' => false,
                'is_paid' => false,
                'formatted_status' => 'Free Plan',
            ];
        }

        $statusLabel = ucfirst($subscription->status);
        if ($subscription->status === 'trialing') {
            $statusLabel .= ' (Trial)';
        } elseif ($subscription->status === 'pending') {
            $statusLabel = 'Pending Payment';
        } elseif ($subscription->status === 'authenticated') {
            $statusLabel = 'Active';
        }

        return [
            'status' => $subscription->status,
            'plan_name' => $subscription->plan->name,
            'razorpay_subscription_id' => $subscription->razorpay_subscription_id,
            'expiry_date' => $subscription->current_period_end ? $subscription->current_period_end->format('Y-m-d\TH:i:s\Z') : 'Never',
            'is_trial' => $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture(),
            'is_paid' => $subscription->plan->price > 0 && in_array($subscription->status, ['active', 'trialing', 'authenticated']),
            'formatted_status' => $statusLabel,
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }
}
