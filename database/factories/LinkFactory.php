<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'url' => $this->faker->url(),
            'is_active' => true,
            'order' => $this->faker->numberBetween(0, 10),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
