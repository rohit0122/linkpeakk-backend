<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Aggregated call for dashboard initialization.
     */
    public function init(Request $request, \App\Services\DashboardService $dashboardService)
    {
        $data = $dashboardService->getInitialData($request->user());

        return ApiResponse::success($data, 'Dashboard initialized successfully');
    }
}
