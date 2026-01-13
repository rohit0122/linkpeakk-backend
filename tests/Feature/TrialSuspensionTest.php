<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Plans
        Plan::firstOrCreate(['slug' => 'free'], [
            'name' => 'Free Plan',
            'price' => 0,
            'trial_days' => 0,
        ]);

        Plan::firstOrCreate(['slug' => 'pro'], [
            'name' => 'Pro Plan',
            'price' => 19,
            'trial_days' => 7,
            'razorpay_plan_id' => 'plan_pro_123'
        ]);
    }

    public function test_user_is_suspended_if_trial_expired_without_active_paid_subscription()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $proPlan = Plan::where('slug', 'pro')->first();

        // Create an expired trial subscription
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'trialing',
            'trial_ends_at' => Carbon::now()->subDay(),
            'current_period_start' => Carbon::now()->subDays(8),
            'current_period_end' => Carbon::now()->subDay(),
        ]);

        // Attempt login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Your trial period has expired. Please contact support to reactivate your account and upgrade to a paid plan.',
        ]);

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->suspended_at);
        $this->assertEquals('Your trial period has expired. Please contact support to reactivate your account and upgrade to a paid plan.', $user->suspension_reason);
    }

    public function test_user_not_suspended_if_active_paid_subscription_exists()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $proPlan = Plan::where('slug', 'pro')->first();

        // Expired trial
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'trialing',
            'trial_ends_at' => Carbon::now()->subDay(),
        ]);

        // BUT has an active paid subscription
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_start' => Carbon::now()->subDay(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        // Attempt login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNull($user->suspended_at);
    }
}
