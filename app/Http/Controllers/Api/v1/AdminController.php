<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BioPage;
use App\Models\Subscription;
use App\Models\Link;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        
        // Paid users are those with an active or trialing subscription on a plan with price > 0
        $paidUsers = User::whereHas('subscriptions', function ($query) {
            $query->whereIn('status', ['active', 'trialing', 'authenticated', 'pending'])
                  ->whereHas('plan', function ($q) {
                      $q->where('price', '>', 0);
                  });
        })->count();

        $conversionRate = $totalUsers > 0 ? round(($paidUsers / $totalUsers) * 100, 2) : 0;

        // 2. Revenue (MRR)
        // Adjust price based on interval if necessary (currently assumes all are monthly based on seeder)
        $mrr = Subscription::whereIn('status', ['active', 'trialing', 'authenticated', 'pending'])
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(DB::raw('SUM(CASE WHEN plans.billing_interval = "year" THEN plans.price / 12 ELSE plans.price END) as total'))
            ->value('total') ?? 0;

        $annualRevenue = $mrr * 12;

        // 3. System-wide Stats
        $totalViews = BioPage::sum('views');
        $totalLinks = Link::count();

        // 4. Chart Data: Plan Distribution
        $planDistribution = DB::table('plans')
            ->leftJoin('subscriptions', 'plans.id', '=', 'subscriptions.plan_id')
            ->whereIn('subscriptions.status', ['active', 'trialing', 'authenticated', 'pending'])
            ->select('plans.name as label', DB::raw('count(subscriptions.id) as value'))
            ->groupBy('plans.name')
            ->get();

        // 5. Chart Data: User Distribution (Active vs Inactive)
        $userDistribution = [
            ['label' => 'Active', 'value' => User::where('is_active', true)->count()],
            ['label' => 'Inactive', 'value' => User::where('is_active', false)->count()],
        ];

        // 6. Growth Charts (Keep existing logic but format for frontend)
        $userGrowth = User::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("DATE(created_at) as date"), DB::raw("DATE_FORMAT(created_at, '%b %d') as label"), DB::raw('count(*) as count'))
            ->groupBy('date', 'label')
            ->orderBy('date')
            ->get();

        return ApiResponse::success([
            'metrics' => [
                'total_users' => $totalUsers,
                'paid_users' => $paidUsers,
                'new_users_30d' => $newUsers30d,
                'conversion_rate' => $conversionRate,
                'mrr' => round($mrr, 2),
                'annual_revenue' => round($annualRevenue, 2),
                'total_views' => $totalViews,
                'total_links' => $totalLinks,
            ],
            'charts' => [
                'plan_distribution' => $planDistribution,
                'user_distribution' => $userDistribution,
                'user_growth' => $userGrowth,
            ]
        ], 'Admin stats retrieved successfully');
    }

    /**
     * List users for admin.
     */
    public function users()
    {
        $users = User::with(['activeSubscription.plan'])->latest()->paginate(20);
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
}
