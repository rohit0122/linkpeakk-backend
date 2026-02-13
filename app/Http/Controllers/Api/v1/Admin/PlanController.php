<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index()
    {
        $plans = Plan::all();
        return ApiResponse::success($plans, 'Plans retrieved successfully');
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'is_active' => 'required|boolean',
            'features' => 'required|array',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan = Plan::create($validated);

        return ApiResponse::success($plan, 'Plan created successfully', 201);
    }

    /**
     * Display the specified plan.
     */
    public function show($id)
    {
        $plan = Plan::findOrFail($id);
        return ApiResponse::success($plan, 'Plan retrieved successfully');
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:plans,slug,'.$id,
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:3',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);

        return ApiResponse::success($plan, 'Plan updated successfully');
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);

        // Check if plan is currently assigned to any users
        if (\App\Models\User::where('plan_id', $plan->id)->exists()) {
            return ApiResponse::error('Cannot delete plan that is currently assigned to users. Deactivate it instead.', 400);
        }

        $plan->delete();

        return ApiResponse::success([], 'Plan deleted successfully');
    }
}
