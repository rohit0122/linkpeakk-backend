<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BioPage;
use App\Models\Subscription;
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
        $totalUsers = User::count();
        $totalViews = BioPage::sum('views');
        
        // MRR Calculation (simplified)
        $mrr = Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        $planDistribution = User::join('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select('plans.name as _id', DB::raw('count(*) as count'))
            ->groupBy('plans.name')
            ->get();

        // User Growth (last 30 days)
        $userGrowth = User::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("DATE(created_at) as date"), DB::raw("DATE_FORMAT(created_at, '%b %d') as label"), DB::raw('count(*) as count'))
            ->groupBy('date', 'label')
            ->orderBy('date')
            ->get();

        // Revenue Growth (simplified MRR contribution by new subscriptions)
        $revenueGrowth = Subscription::where('subscriptions.created_at', '>=', now()->subDays(30))
            ->where('subscriptions.status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(DB::raw("DATE(subscriptions.created_at) as date"), DB::raw("DATE_FORMAT(subscriptions.created_at, '%b %d') as label"), DB::raw('SUM(plans.price) as amount'))
            ->groupBy('date', 'label')
            ->orderBy('date')
            ->get();

        return ApiResponse::success([
            'total_users' => $totalUsers,
            'total_views' => $totalViews,
            'mrr' => $mrr,
            'plan_distribution' => $planDistribution,
            'user_growth' => $userGrowth,
            'revenue_growth' => $revenueGrowth
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
