<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 50, 500),
            'image' => $this->faker->imageUrl(640, 480, 'room', true),
        ];
    }
}
