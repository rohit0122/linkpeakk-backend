<?php

namespace App\Services;

use App\Models\BioPage;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

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

        // Plan Info
        $data['subscription'] = $this->formatSubscription($user);

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
    public function formatSubscription(User $user)
    {
        $plan = $user->plan;

        if (! $plan || $plan->slug === 'free') {
            return [
                'status' => 'free',
                'plan_name' => $plan ? $plan->name : 'FREE',
                'expiry_date' => null,
                'formatted_status' => 'Free Plan',
            ];
        }

        $isExpired = $user->plan_expires_at && $user->plan_expires_at->isPast();

        // Check payment history to determine if this is a trial or active paid sub
        $hasPaid = $user->payments()->where('status', 'captured')->exists();
        $isTrial = ! $hasPaid && $plan->price > 0;

        if ($isExpired) {
            $status = 'expired';
            $statusLabel = 'Expired';
        } elseif ($isTrial) {
            $status = 'trial';
            $statusLabel = 'Trialing';
        } else {
            $status = 'active';
            $statusLabel = 'Active';
        }
        $now = Carbon::now();

        return [
            'status' => $status,
            'plan_name' => $plan->name,
            'expiry_date' => $user->plan_expires_at ? $user->plan_expires_at->format('Y-m-d\TH:i:s\Z') : null,
            'is_trial' => $isTrial,
            'formatted_status' => $statusLabel,
            'is_renewal_window_open' => $user->plan_expires_at ? ($user->plan_expires_at->between($now, $now->copy()->addDays(7))) : true,
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'pending_plan' => $user->pendingPlan ? [
                'name' => $user->pendingPlan->name,
                'slug' => $user->pendingPlan->slug,
            ] : null,
        ];
    }
}
