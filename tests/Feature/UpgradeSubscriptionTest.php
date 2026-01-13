<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class UpgradeSubscriptionTest extends TestCase
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

    public function test_free_user_can_upgrade_to_pro()
    {
        // 1. Create User and Free Subscription
        $user = User::factory()->create(['email_verified_at' => now()]);
        $freePlan = Plan::where('slug', 'free')->first();
        
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'current_period_start' => now(),
        ]);

        $this->actingAs($user);

        // 2. Mock Razorpay
        $this->mock(RazorpayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createCustomer')->once()->andReturn((object)['id' => 'cust_upgrade_123']);
            $mock->shouldReceive('createSubscription')->once()->andReturn((object)['id' => 'sub_upgrade_123']);
        });

        // 3. Request Upgrade
        $response = $this->postJson('/api/v1/subscriptions/select-plan', [
            'plan_slug' => 'pro'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.razorpay_subscription_id', 'sub_upgrade_123');

        // 4. Verify Active Subscription is now Pro/Trialing with NO trial days
        $activeSub = $user->fresh()->activeSubscription;
        $this->assertEquals('pro', $activeSub->plan->slug);
        $this->assertEquals('trialing', $activeSub->status);
        $this->assertEquals('sub_upgrade_123', $activeSub->razorpay_subscription_id);
        
        // Ensure trial_ends_at is roughly now (not 7 days in future)
        $this->assertTrue($activeSub->trial_ends_at->diffInMinutes(now()) < 1);
    }
}
