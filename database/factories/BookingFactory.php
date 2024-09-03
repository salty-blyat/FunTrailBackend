<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => \App\Models\Hotel::factory(),
            'user_id' => \App\Models\User::factory(),
            'check_in' => $this->faker->date,
            'check_out' => $this->faker->date,
            'sum_total' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
}