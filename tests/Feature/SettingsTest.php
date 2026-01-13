<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/settings/profile', [
            'name' => 'New Name',
            'bio' => 'New developer bio'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);
            
        $this->assertDatabaseHas('users', ['bio' => 'New developer bio']);
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->actingAs($user)->putJson('/api/v1/settings/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword'
        ]);

        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }
}
