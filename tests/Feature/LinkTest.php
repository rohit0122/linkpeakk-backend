<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_links()
    {
        $user = User::factory()->create();
        Link::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/links');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_link()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/links', [
            'title' => 'My Website',
            'url' => 'https://example.com'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'My Website']);

        $this->assertDatabaseHas('links', ['title' => 'My Website']);
    }

    public function test_user_can_update_link()
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/links/{$link->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_user_can_delete_link()
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/links/{$link->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }
}
