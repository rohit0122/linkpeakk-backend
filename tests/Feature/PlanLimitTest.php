<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Plans are seeded in TestCase or we need to seed them here if RefreshDatabase wipes them.
        // RefreshDatabase wipes, so re-seed.
        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_free_plan_limit_for_links()
    {
        $user = User::factory()->create();
        $freePlan = Plan::where('slug', 'free')->first();

        // Assign Free Subscription
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Create 5 links (Allowed)
        Link::factory()->count(5)->create(['user_id' => $user->id]);

        // Try to create 6th link (Should Fail)
        $response = $this->actingAs($user)->postJson('/api/v1/links', [
            'title' => 'Link 6',
            'url' => 'https://example.com/6'
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Plan limit reached. Upgrade to create more links.']);
    }

    public function test_pro_plan_allows_more_links()
    {
        $user = User::factory()->create();
        $proPlan = Plan::where('slug', 'pro')->first();

        // Assign Pro Subscription
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Create 5 links
        Link::factory()->count(5)->create(['user_id' => $user->id]);

        // Try to create 6th link (Should Pass)
        $response = $this->actingAs($user)->postJson('/api/v1/links', [
            'title' => 'Link 6',
            'url' => 'https://example.com/6'
        ]);

        $response->assertStatus(200);
    }

    public function test_user_without_subscription_defaults_to_active_free_plan_limits()
    {
        // If logic defaults to FREE plan when no sub exists
        $user = User::factory()->create();

        // Create 5 links
        Link::factory()->count(5)->create(['user_id' => $user->id]);

        // Try to create 6th link
        $response = $this->actingAs($user)->postJson('/api/v1/links', [
            'title' => 'Link 6',
            'url' => 'https://example.com/6'
        ]);

        $response->assertStatus(403);
    }
}
