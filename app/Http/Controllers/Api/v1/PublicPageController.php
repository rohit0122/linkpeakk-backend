<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Analytics;
use App\Models\BioPage;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicPageController extends Controller
{
    /**
     * Fetch page data for rendering the public bio page.
     */
    public function show($slug)
    {
        $cacheKey = "public_page_{$slug}";

        $data = cache()->remember($cacheKey, now()->addMinutes(5), function () use ($slug) {
            $page = BioPage::where('slug', $slug)
                ->where('is_active', true)
                ->with(['user.plan'])
                ->first();

            if (! $page) {
                return null;
            }

            $links = $page->links()
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['title', 'url', 'icon']); // Adjust fields as needed

            // The original links query is now moved inside the 'page' array
            // and its fields are adjusted as per the instruction's code edit.
            // The top-level $links variable is no longer needed.

            $userPlanName = $page->user->plan?->name ?? 'FREE';

            return [
                'page' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'bio' => $page->bio,
                    'template' => $page->template,
                    'theme' => $page->theme,
                    'profile_image' => $page->profile_image,
                    'seo' => $page->seo,
                    'social_links' => $page->social_links,
                    'total_views' => $page->total_views,
                    'unique_views' => $page->unique_views,
                    'likes' => $page->likes,
                    'branding' => $page->branding ?? [
                        'removeWatermark' => false,
                        'customText' => 'Powered by LinkPeakk',
                        'customUrl' => config('app.url'),
                    ],
                    'is_sensitive' => $page->is_sensitive,
                    'show_branding' => $page->show_branding,
                    'user' => [
                        'name' => $page->user->name,
                        'avatar_url' => $page->user->avatar_url,
                        'plan' => $userPlanName,
                    ],
                    'links' => $page->links()
                        ->where('is_active', true)
                        ->orderBy('order')
                        ->get(['id', 'user_id', 'bio_page_id', 'title', 'url', 'icon', 'is_active', 'order'/* 'clicks', 'unique_clicks', 'created_at', 'updated_at' */]),
                ],
                // The top-level 'links' and 'userPlan' are removed as they are now nested.
            ];
        });

        if (! $data) {
            return ApiResponse::error('Bio page not found', 404);
        }

        return ApiResponse::success($data, 'Bio page retrieved successfully');
    }

    /**
     * Fetch lightweight stats for a bio page.
     * Designed for high-frequency polling.
     */
    public function stats($slug)
    {
        // Cache for 5 seconds to handle high concurrency (e.g. 200k users)
        // With 5s TTL, DB is hit max once per 5s per page, regardless of traffic.
        $stats = \Illuminate\Support\Facades\Cache::remember("page_stats_{$slug}", 5, function () use ($slug) {
            return DB::table('bio_pages')
                ->where('slug', $slug)
                ->select('views as total_views', 'unique_views', 'likes')
                ->first();
        });

        if (! $stats) {
            return ApiResponse::error('Bio page not found', 404);
        }

        return response()->json($stats);
    }

    /**
     * Track a view for a bio page.
     */
    public function trackView(Request $request)
    {
        $request->validate(['pageId' => 'required|exists:bio_pages,id']);
        $pageId = $request->pageId;
        $ip = $request->ip();
        $cacheKey = "track_view_{$pageId}_{$ip}";

        $isUnique = ! cache()->has($cacheKey);
        if ($isUnique) {
            cache()->put($cacheKey, true, now()->addDay());
        }

        DB::transaction(function () use ($pageId, $isUnique) {
            $update = ['views' => DB::raw('views + 1')];
            if ($isUnique) {
                $update['unique_views'] = DB::raw('unique_views + 1');
            }
            BioPage::where('id', $pageId)->update($update);

            $analyticsUpdate = ['count' => DB::raw('count + 1')];
            if ($isUnique) {
                $analyticsUpdate['unique_count'] = DB::raw('unique_count + 1');
            }

            /*Analytics::updateOrCreate(
                ['bio_page_id' => $pageId, 'link_id' => null, 'type' => 'view', 'date' => now()->toDateString()],
                $analyticsUpdate
            );*/
            $analytics = Analytics::firstOrCreate(
                [
                    'bio_page_id' => $pageId,
                    'link_id' => null,
                    'type' => 'view',
                    'date' => now()->toDateString(),
                ],
                [
                    'count' => 0,
                    'unique_count' => 0,
                ]
            );

            $analytics->increment('count');

            if ($isUnique) {
                $analytics->increment('unique_count');
            }
        });

        return ApiResponse::success([], 'View tracked successfully');
    }

    /**
     * Track a click for a specific link on a bio page.
     */
    public function trackClick(Request $request)
    {
        $request->validate([
            'pageId' => 'required|exists:bio_pages,id',
            'linkId' => 'required|exists:links,id',
        ]);

        $pageId = $request->pageId;
        $linkId = $request->linkId;
        $ip = $request->ip();
        $cacheKey = "track_click_{$linkId}_{$ip}";

        $isUnique = ! cache()->has($cacheKey);
        if ($isUnique) {
            cache()->put($cacheKey, true, now()->addDay());
        }

        DB::transaction(function () use ($pageId, $linkId, $isUnique) {
            $update = ['clicks' => DB::raw('clicks + 1')];
            if ($isUnique) {
                $update['unique_clicks'] = DB::raw('unique_clicks + 1');
            }
            Link::where('id', $linkId)->update($update);

            $analyticsUpdate = ['count' => DB::raw('count + 1')];
            if ($isUnique) {
                $analyticsUpdate['unique_count'] = DB::raw('unique_count + 1');
            }

            /*Analytics::updateOrCreate(
                ['bio_page_id' => $pageId, 'link_id' => $linkId, 'type' => 'click', 'date' => now()->toDateString()],
                $analyticsUpdate
            );*/
            $analytics = Analytics::firstOrCreate(
                [
                    'bio_page_id' => $pageId,
                    'link_id' => $linkId,
                    'type' => 'click',
                    'date' => now()->toDateString(),
                ],
                [
                    'count' => 0,
                    'unique_count' => 0,
                ]
            );

            $analytics->increment('count');

            if ($isUnique) {
                $analytics->increment('unique_count');
            }
        });

        return ApiResponse::success([], 'Click tracked successfully');
    }

    /**
     * Track a like for a bio page.
     */
    public function trackLike(Request $request)
    {
        $request->validate(['pageId' => 'required|exists:bio_pages,id']);
        $pageId = $request->pageId;
        $ip = $request->ip();
        $cacheKey = "track_like_{$pageId}_{$ip}";

        // Likes are unique per IP for 24h (or handle it otherwise if logged in)
        if (cache()->has($cacheKey)) {
            return ApiResponse::success([], 'Already liked');
        }
        cache()->put($cacheKey, true, now()->addDay());

        DB::transaction(function () use ($pageId) {
            BioPage::where('id', $pageId)->increment('likes');

            /* Analytics::updateOrCreate(
                 ['bio_page_id' => $pageId, 'link_id' => null, 'type' => 'like', 'date' => now()->toDateString()],
                 ['count' => DB::raw('count + 1')]
             );*/
            $analytics = Analytics::firstOrCreate(
                [
                    'bio_page_id' => $pageId,
                    'link_id' => null,
                    'type' => 'like',
                    'date' => now()->toDateString(),
                ],
                ['count' => 0]
            );

            $analytics->increment('count');

        });

        return ApiResponse::success([], 'Like tracked successfully');
    }
}
