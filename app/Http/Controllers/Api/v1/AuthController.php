<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request, SubscriptionService $subscriptionService)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'plan' => 'nullable|string|exists:plans,slug',
            ]);

            $token = \Illuminate\Support\Str::random(60);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'verification_token' => $token,
            ]);

            // Handle Plan Subscription
            $planSlug = strtolower($request->input('plan', 'free'));
            $plan = Plan::where('slug', $planSlug)->first();

            if ($planSlug === 'agency') {
                $user->update(['role' => 'agency']);
            }

            if ($plan) {
                if ($plan->slug === 'free') {
                    // Create explicit free subscription
                    Subscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'current_period_start' => Carbon::now(),
                        // Free plan has no end date usually
                    ]);
                } else {
                    // For Paid plans, create a pending local subscription
                    // Razorpay id will be created after email verification
                    $trialEndsAt = Carbon::now()->addDays($plan->trial_days ?: 7);
                    Subscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'pending', // Special status for unverified emails
                        'current_period_start' => Carbon::now(),
                        'current_period_end' => $trialEndsAt,
                        'trial_ends_at' => $trialEndsAt,
                    ]);
                }
            }

            // Send verification email
            $user->notify(new \App\Notifications\VerifyEmailNotification);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email address.',
                'data' => [],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Registration Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: '.$e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Verify user email with token.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = User::where('verification_token', $request->token)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification token.',
                'data' => [],
            ], 400);
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->save();

        // Trigger Laravel Verified event
        event(new \Illuminate\Auth\Events\Verified($user));

        // Send Welcome Email
        $user->notify(new WelcomeNotification);

        // Check for pending paid subscription to initialize Razorpay
        $pendingSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingSubscription) {
            try {
                // Initialize Razorpay Subscription (Customer + Subscription)
                \Illuminate\Support\Facades\Log::info('Attempting to initialize Razorpay subscription for verified user: '.$user->id);
                $subscriptionService = app(\App\Services\SubscriptionService::class);
                $subscriptionService->initializeRazorpaySubscription($pendingSubscription);
                \Illuminate\Support\Facades\Log::info('Razorpay subscription successfully initialized for user: '.$user->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Razorpay initialization FAILED after verification for user '.$user->id.': '.$e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $user->id,
                    'plan' => $pendingSubscription->plan->slug ?? 'unknown',
                    'razorpay_plan_id' => $pendingSubscription->plan->razorpay_plan_id ?? 'missing',
                ]);

                // We don't want to break the verification flow if Razorpay fails (e.g. invalid plan IDs)
                // but the user should be aware their premium features might be pending setup.
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully. Please login to access your account.',
            'data' => [],
        ]);
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified.',
                'data' => [],
            ], 400);
        }

        if (! $user->verification_token) {
            $user->verification_token = \Illuminate\Support\Str::random(60);
            $user->save();
        }

        $user->notify(new \App\Notifications\VerifyEmailNotification);

        return response()->json([
            'success' => true,
            'message' => 'Verification email resent successfully.',
            'data' => [],
        ]);
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request, \App\Services\DashboardService $dashboardService)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials.',
                    'data' => [],
                ], 401);
            }

            // Check and handle trial expiry suspension
            $subscriptionService = app(SubscriptionService::class);
            $subscriptionService->checkAndHandleTrialExpiry($user);

            // Double check if user is now suspended
            if (! $user->is_active || $user->suspended_at) {
                return response()->json([
                    'success' => false,
                    'message' => $user->suspension_reason ?: 'Your account is suspended. Please contact support to reactivate it.',
                    'data' => [],
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $initialData = $dashboardService->getInitialData($user);

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => array_merge($initialData, [
                    'token' => $token,
                    /* 'token_type' => 'Bearer', */
                ]),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
            'data' => [],
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => [],
            ], 404);
        }

        $token = \Illuminate\Support\Str::random(60);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        $user->notify(new \App\Notifications\PasswordResetLink($token));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email.',
            'data' => [],
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (! $reset) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token or email.',
                'data' => [],
            ], 400);
        }

        // Token expiry check (e.g., 60 minutes)
        if (now()->subMinutes(60)->gt($reset->created_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired.',
                'data' => [],
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => [],
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Notify user
        $user->notify(new \App\Notifications\PasswordChanged);

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully.',
            'data' => [],
        ]);
    }
}
