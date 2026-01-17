<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BioPage;
use App\Models\Analytics;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Fetch total lifetime counts for Stats Cards.
     */
    public function lifetime(Request $request)
    {
        $request->validate(['pageId' => 'required|exists:bio_pages,id']);
        $pageId = $request->pageId;

        // Ensure user owns the page
        $page = $request->user()->bioPages()->findOrFail($pageId);

        $stats = $page->links()
            ->selectRaw('SUM(clicks) as aggregated_clicks, SUM(unique_clicks) as aggregated_unique_clicks, COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count')
            ->first();

        // Fallback for null values if no links exist
        $totalClicks = (int) ($stats->aggregated_clicks ?? 0);
        $uniqueClicks = (int) ($stats->aggregated_unique_clicks ?? 0);
        $activeLinks = (int) ($stats->active_count ?? 0);

        $totalViews = $page->views;
        $avgCTR = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        return ApiResponse::success([
            'lifetime' => [
                'total_views' => $totalViews,
                'unique_views' => $page->unique_views,
                'total_clicks' => $totalClicks,
                'unique_clicks' => $uniqueClicks,
                'total_likes' => $page->likes,
                'total_active_links' => $activeLinks,
                'avg_ctr' => $avgCTR // Percentage
            ]
        ], 'Lifetime stats retrieved successfully');
    }

    /**
     * Fetch chart data with daily/weekly/monthly aggregation.
     */
    public function charts(Request $request)
    {
        $request->validate([
            'pageId' => 'required|exists:bio_pages,id',
            'range' => 'required|in:7,15,30,90,180,9999'
        ]);

        $pageId = $request->pageId;
        $range = (int) $request->range;

        // Ensure user owns the page
        $page = $request->user()->bioPages()->findOrFail($pageId);
        $user = $request->user();

        // Plan-based access control for date ranges
        $allowedRanges = $this->getAllowedRanges($user);
        if (!in_array($range, $allowedRanges)) {
            return ApiResponse::error('Upgrade your plan to access this date range.', [], 403);
        }

        $startDate = $this->getStartDate($range);
        $aggregation = $this->getAggregationType($range);

        // Fetch summary data (Views & Clicks)
        $summaryChart = $this->getSummaryChartData($pageId, $startDate, $aggregation, $range);

        // Fetch detailed links data
        $detailedLinksChart = $this->getDetailedLinksChartData($pageId, $startDate, $aggregation, $range);

        return ApiResponse::success([
            'meta' => ['aggregation' => $aggregation, 'range' => $range],
            'data' => [
                'summaryChart' => $summaryChart,
                'detailedLinksChart' => $detailedLinksChart
            ]
        ], 'Chart data retrieved successfully');
    }

    private function getStartDate($range)
    {
        return match ($range) {
            7 => now()->subDays(7),
            15 => now()->subDays(15),
            30 => now()->subMonth(),
            90 => now()->subMonths(3),
            180 => now()->subMonths(6),
            9999 => now()->subYears(10), // Arbitrary long time
            default => now()->subDays(7),
        };
    }

    private function getAggregationType($range)
    {
        return match ($range) {
            7, 15, 30 => 'daily',
            90 => 'weekly',
            180, 9999 => 'monthly',
            default => 'daily',
        };
    }

    private function getSummaryChartData($pageId, $startDate, $aggregation, $range)
    {
        $query = Analytics::where('bio_page_id', $pageId)
            ->where('date', '>=', $startDate)
            ->get();

        // If no data, return empty structure for the date range
        if ($query->isEmpty()) {
            return $this->generateEmptyChartData($range, $aggregation);
        }

        // Group by label in PHP
        $grouped = $query->groupBy(function ($item) use ($aggregation) {
            return $this->formatChartLabel(Carbon::parse($item->date), $aggregation);
        });

        $results = [];
        $chartDates = $this->generateChartDates($range, $aggregation);

        foreach ($chartDates as $dateInfo) {
            $label = $dateInfo['label'];
            $group = $grouped->get($label);
            
            $totalViews = 0;
            $uniqueViews = 0;
            $totalClicks = 0;
            $uniqueClicks = 0;

            if ($group) {
                foreach ($group as $item) {
                    if ($item->type === 'view') {
                        $totalViews += $item->count;
                        $uniqueViews += $item->unique_count;
                    } elseif ($item->type === 'click') {
                        $totalClicks += $item->count;
                        $uniqueClicks += $item->unique_count;
                    }
                }
            }

            $results[] = [
                'label' => $label,
                'date' => $dateInfo['date'],
                'total_views' => $totalViews,
                'unique_views' => $uniqueViews,
                'total_clicks' => $totalClicks,
                'unique_clicks' => $uniqueClicks,
            ];
        }

        return collect($results);
    }

    private function getDetailedLinksChartData($pageId, $startDate, $aggregation, $range)
    {
        // Get the page to access its links
        $page = BioPage::with('links:id,bio_page_id,title')->find($pageId);
        
        $query = Analytics::where('bio_page_id', $pageId)
            ->where('date', '>=', $startDate)
            ->whereNotNull('link_id')
            ->with('link:id,title')
            ->get();

        // If no data, return empty structure with all links
        if ($query->isEmpty()) {
            return $this->generateEmptyLinksChartData($range, $aggregation, $page);
        }

        // Group by label in PHP
        $grouped = $query->groupBy(function ($item) use ($aggregation) {
            return $this->formatChartLabel(Carbon::parse($item->date), $aggregation);
        });

        $chartDates = $this->generateChartDates($range, $aggregation);
        $results = [];

        foreach ($chartDates as $dateInfo) {
            $label = $dateInfo['label'];
            $group = $grouped->get($label);
            $dayData = [
                'label' => $label,
                'date' => $dateInfo['date']
            ];

            // Initialize all links with 0
            if ($page && $page->links) {
                foreach ($page->links as $link) {
                    $dayData[$link->title] = [
                        'total_clicks' => 0,
                        'unique_clicks' => 0,
                    ];
                }
            }

            if ($group) {
                foreach ($group as $item) {
                    $linkTitle = $item->link->title ?? 'Unknown Link';
                    // Initialize if not present (e.g., link deleted but in history)
                    if (!isset($dayData[$linkTitle])) {
                        $dayData[$linkTitle] = [
                            'total_clicks' => 0,
                            'unique_clicks' => 0,
                        ];
                    }
                    
                    $dayData[$linkTitle]['total_clicks'] += (int)$item->count;
                    $dayData[$linkTitle]['unique_clicks'] += (int)$item->unique_count;
                }
            }
            
            $results[] = $dayData;
        }

        return $results;
    }

    private function generateChartDates($range, $aggregation) {
        $days = match ($range) {
            7 => 7,
            15 => 15,
            30 => 30,
            90 => 90,
            180 => 180,
            9999 => 365,
            default => 7,
        };

        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dates[] = [
                'label' => $this->formatChartLabel($date, $aggregation),
                'date' => $date->startOfDay()->format('Y-m-d\TH:i:s\Z')
            ];
        }
        return $dates;
    }

    /**
     * Format chart label consistently for both SQL and PHP-generated data.
     */
    private function formatChartLabel($date, $aggregation)
    {
        return match ($aggregation) {
            'daily' => $date->format('M d'),
            'weekly' => $date->format('M') . ' W' . $date->format('W'),
            'monthly' => $date->format('M Y'),
            default => $date->format('M d'),
        };
    }

    /**
     * Get allowed date ranges based on user's plan.
     * Free: 7d only
     * Pro: 7d, 15d, 1m, 3m
     * Agency: All ranges
     */
    private function getAllowedRanges($user)
    {
        $ranges = [7, 15, 30, 90, 180, 9999];
        return array_filter($ranges, fn($r) => $user->canAccessFeature('analytics', $r));
    }

    /**
     * Generate empty chart data for the given range when no analytics exist.
     */
    private function generateEmptyChartData($range, $aggregation)
    {
        $days = match ($range) {
            7 => 7,
            15 => 15,
            30 => 30,
            90 => 90,
            180 => 180,
            9999 => 365,
            default => 7,
        };

        $chartDates = $this->generateChartDates($range, $aggregation);
        $data = [];
        
        foreach ($chartDates as $dateInfo) {
            $data[] = [
                'label' => $dateInfo['label'],
                'date' => $dateInfo['date'],
                'total_views' => 0,
                'unique_views' => 0,
                'total_clicks' => 0,
                'unique_clicks' => 0,
            ];
        }

        return collect($data);
    }

    /**
     * Generate empty link chart data for the given range when no analytics exist.
     */
    private function generateEmptyLinksChartData($range, $aggregation, $page)
    {
        if (!$page || !$page->links || $page->links->isEmpty()) {
            return [];
        }

        $days = match ($range) {
            7 => 7,
            15 => 15,
            30 => 30,
            90 => 90,
            180 => 180,
            9999 => 365,
            default => 7,
        };

        $chartDates = $this->generateChartDates($range, $aggregation);
        $data = [];
        
        foreach ($chartDates as $dateInfo) {
            $dayData = [
                'label' => $dateInfo['label'],
                'date' => $dateInfo['date']
            ];
            
            // Add each link with zero clicks
            foreach ($page->links as $link) {
                $dayData[$link->title] = [
                    'total_clicks' => 0,
                    'unique_clicks' => 0,
                ];
            }
            
            $data[] = $dayData;
        }

        return $data;
    }
}
