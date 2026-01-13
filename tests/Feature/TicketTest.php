<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_ticket()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/tickets', [
            'subject' => 'Help needed',
            'message' => 'I have an issue.',
            'priority' => 'high'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['subject' => 'Help needed']);
    }

    public function test_user_can_list_tickets()
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
