<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BioPage;
use App\Models\Payment;
use App\Models\Link;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Metrics for the super-admin dashboard.
     */
    public function stats()
    {
        // 1. User Statistics
        $totalUsers = User::count();
        $newUsers30d = User::where('created_at', '>=', now()->subDays(30))->count();
        
        // Paid users are those with a plan_expires_at in the future and price > 0
        $paidUsers = User::whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '>=', now())
            ->whereHas('plan', function ($q) {
                $q->where('price', '>', 0);
            })->count();

        $conversionRate = $totalUsers > 0 ? round(($paidUsers / $totalUsers) * 100, 2) : 0;

        // 2. Revenue (Last 30 Days)
        $revenue30d = Payment::where('status', 'captured')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $annualRevenue = $revenue30d * 12; // Estimated

        // 3. System-wide Stats
        $totalViews = BioPage::sum('views');
        $totalLinks = Link::count();

        // 4. Chart Data: Plan Distribution (Includes Free users)
        $planDistribution = DB::table('plans')
            ->leftJoin('users', 'plans.id', '=', 'users.plan_id')
            ->select('plans.name as label', DB::raw('count(users.id) as value'))
            ->groupBy('plans.name')
            ->get();

        // 5. Chart Data: User Distribution (Active vs Inactive)
        $userDistribution = [
            ['label' => 'Active', 'value' => User::where('is_active', true)->count()],
            ['label' => 'Inactive', 'value' => User::where('is_active', false)->count()],
        ];

        // 6. Growth Charts: User Growth
        $userGrowth = User::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("DATE(created_at) as date_val"), DB::raw('count(*) as count'))
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get()
            ->map(function ($item) {
                $date = Carbon::parse($item->date_val);
                return [
                    'label' => $date->format('M d'),
                    'date' => $date->startOfDay()->format('Y-m-d\TH:i:s\Z'),
                    'count' => $item->count
                ];
            });

        // 7. Growth Charts: Revenue Growth
        $revenueGrowth = Payment::where('status', 'captured')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("DATE(created_at) as date_val"), DB::raw('sum(amount) as total'))
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get()
            ->map(function ($item) {
                $date = Carbon::parse($item->date_val);
                return [
                    'label' => $date->format('M d'),
                    'date' => $date->startOfDay()->format('Y-m-d\TH:i:s\Z'),
                    'value' => round($item->total, 2)
                ];
            });

        // 8. Business Intelligence Metrics
        $topPages = BioPage::with('user:id,name')->orderBy('views', 'desc')->take(5)->get(['id', 'user_id', 'slug', 'title', 'views']);
        $expiringSoonCount = User::whereNotNull('plan_expires_at')
            ->whereBetween('plan_expires_at', [now(), now()->addDays(7)])
            ->count();
        
        $revenueByCurrency = Payment::where('status', 'captured')
            ->select('currency', DB::raw('sum(amount) as total'))
            ->groupBy('currency')
            ->get();

        return ApiResponse::success([
            'metrics' => [
                'total_users' => $totalUsers,
                'paid_users' => $paidUsers,
                'new_users_30d' => $newUsers30d,
                'conversion_rate' => $conversionRate,
                'revenue_30d' => round($revenue30d, 2),
                'annual_revenue_est' => round($annualRevenue, 2),
                'total_views' => $totalViews,
                'total_links' => $totalLinks,
                'expiring_soon' => $expiringSoonCount,
            ],
            'top_pages' => $topPages,
            'revenue_breakdown' => $revenueByCurrency,
            'charts' => [
                'revenue_growth' => $revenueGrowth,
                'user_growth' => $userGrowth,
                'plan_distribution' => $planDistribution,
                'user_distribution' => $userDistribution,
            ]
        ], 'Admin stats retrieved successfully');
    }

    /**
     * List users for admin with search and filters.
     */
    public function users(Request $request)
    {
        $query = User::with(['plan']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->plan_id) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(20);
        return ApiResponse::success($users, 'Users retrieved successfully');
    }

    /**
     * Suspend or unsuspend a user.
     */
    public function suspend(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',
            'suspend' => 'required|boolean'
        ]);

        $user = User::findOrFail($request->userId);
        
        $user->update([
            'is_active' => !$request->suspend,
            'suspended_at' => $request->suspend ? now() : null
        ]);

        $status = $request->suspend ? 'suspended' : 'activated';
        return ApiResponse::success([], "User {$status} successfully");
    }

    /**
     * List all payments for admin with filters.
     */
    public function indexPayments(Request $request)
    {
        $query = Payment::with(['user:id,name,email', 'plan:id,name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $payments = $query->latest()->paginate(20);
        return ApiResponse::success($payments, 'Payments retrieved successfully');
    }

    /**
     * List all bio pages for admin.
     */
    public function indexPages(Request $request)
    {
        $query = BioPage::with('user:id,name,email');
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        $pages = $query->latest()->paginate(20);
        return ApiResponse::success($pages, 'Bio pages retrieved successfully');
    }

    /**
     * List webhook logs for monitoring.
     */
    public function indexWebhookLogs(Request $request)
    {
        $query = \App\Models\WebhookLog::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $logs = $query->latest()->paginate(50);
        return ApiResponse::success($logs, 'Webhook logs retrieved successfully');
    }

    /**
     * Show detailed user info.
     */
    public function showUser($id)
    {
        $user = User::with(['plan', 'pendingPlan', 'bioPages', 'payments.plan'])->findOrFail($id);
        return ApiResponse::success($user, 'User details retrieved successfully');
    }

    /**
     * Override a user's plan manually.
     */
    public function overridePlan(Request $request, $id)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'expiry_date' => 'nullable|date|after:now',
        ]);

        $user = User::findOrFail($id);
        $plan = \App\Models\Plan::findOrFail($request->plan_id);
        
        $expiry = $request->expiry_date ? Carbon::parse($request->expiry_date) : now()->addDays(30);

        $user->update([
            'plan_id' => $plan->id,
            'plan_expires_at' => $expiry,
            'pending_plan_id' => null,
        ]);

        return ApiResponse::success($user->load('plan'), "User plan overridden to {$plan->name} until {$expiry->toDateTimeString()}");
    }
}
