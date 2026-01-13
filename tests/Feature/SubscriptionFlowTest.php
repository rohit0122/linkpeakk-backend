<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Plan::firstOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'price' => 20,
            'razorpay_plan_id' => 'plan_pro_123',
            'trial_days' => 7,
            'features' => []
        ]);

        Plan::firstOrCreate(['slug' => 'free'], [
            'name' => 'Free',
            'price' => 0,
            'features' => []
        ]);
    }

    public function test_registration_creates_pending_subscription_for_paid_plan()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan' => 'pro'
        ]);

        $response->assertStatus(201);
        
        $user = User::where('email', 'test@example.com')->first();
        $subscription = Subscription::where('user_id', $user->id)->first();

        $this->assertEquals('pending', $subscription->status);
        $this->assertNull($subscription->razorpay_subscription_id);
    }

    public function test_email_verification_initializes_razorpay_subscription()
    {
        Notification::fake();

        // 1. Register
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan' => 'pro'
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $token = $user->verification_token;

        // 2. Mock Razorpay Service
        $this->mock(RazorpayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createCustomer')->once()->andReturn((object)['id' => 'cust_123']);
            $mock->shouldReceive('createSubscription')->once()->andReturn((object)['id' => 'sub_123']);
        });

        // 3. Verify Email
        $response = $this->postJson('/api/v1/auth/verify', [
            'token' => $token
        ]);

        $response->assertStatus(200);

        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertEquals('trialing', $subscription->status);
        $this->assertEquals('sub_123', $subscription->razorpay_subscription_id);
    }

    public function test_dashboard_init_returns_razorpay_id_for_trialing_user()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $plan = Plan::where('slug', 'pro')->first();
        
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'razorpay_subscription_id' => 'sub_test_999',
            'status' => 'trialing',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(7),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/dashboard/init');

        $response->assertStatus(200)
            ->assertJsonPath('data.subscription.razorpay_subscription_id', 'sub_test_999');
    }
}
