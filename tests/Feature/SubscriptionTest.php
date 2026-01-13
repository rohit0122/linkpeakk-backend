<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed plans
        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_user_can_view_subscription_status_when_no_subscription()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/subscription/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => false,
                    'plan' => null,
                ]
            ]);
    }

    public function test_user_can_select_plan_and_start_trial()
    {
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro')->first();

        // Mock RazorpayService
        $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('createCustomer')->andReturn((object)['id' => 'cust_123']);
            $mock->shouldReceive('createSubscription')->andReturn((object)['id' => 'sub_123', 'status' => 'created']);
        });

        $response = $this->actingAs($user)->postJson('/api/v1/subscription/select', [
            'plan_slug' => $plan->slug,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Plan selected successfully. Trial started.',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'razorpay_subscription_id' => 'sub_123',
        ]);
    }

    public function test_user_can_cancel_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro')->first();
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'razorpay_subscription_id' => 'sub_existing_123',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Mock RazorpayService
        $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('cancelSubscription')->once()->andReturn((object)['status' => 'cancelled']);
        });

        $response = $this->actingAs($user)->postJson('/api/v1/subscription/cancel');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
            ]);

        $this->assertNotNull($subscription->fresh()->cancelled_at);
    }
}
