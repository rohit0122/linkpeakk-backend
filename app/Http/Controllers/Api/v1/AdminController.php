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

        // 4. Chart Data: Plan Distribution
        $planDistribution = DB::table('plans')
            ->leftJoin('users', 'plans.id', '=', 'users.plan_id')
            ->whereNotNull('users.plan_expires_at')
            ->where('users.plan_expires_at', '>=', now())
            ->select('plans.name as label', DB::raw('count(users.id) as value'))
            ->groupBy('plans.name')
            ->get();

        // 5. Chart Data: User Distribution (Active vs Inactive)
        $userDistribution = [
            ['label' => 'Active', 'value' => User::where('is_active', true)->count()],
            ['label' => 'Inactive', 'value' => User::where('is_active', false)->count()],
        ];

        // 6. Growth Charts (Keep existing logic but format for frontend)
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
        $users = User::with(['plan'])->latest()->paginate(20);
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
     * List all payments for admin.
     */
    public function indexPayments()
    {
        $payments = Payment::with(['user', 'plan'])->latest()->paginate(20);
        return ApiResponse::success($payments, 'Payments retrieved successfully');
    }
}
