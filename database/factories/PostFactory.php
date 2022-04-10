<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->name(),
            'title' => $this->faker->unique()->safeEmail(),
            'event' => now(),
            'time' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'calorie' => Str::random(10),
            'average_speed' => 1,
            'altitude' => 1,
            'distance' => 1,
            'content' => $this->faker->text(),
            'track_id' => ,
            'gps_id' => ,
            'kind' => ,
            'date' => ,
        ];
    }
}
