<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'short_name' => $this->faker->bothify('##'),
            'color' => $this->faker->hexColor(),
            'type' => $this->faker->randomElement([
                0, 1, 3, 11, 2, 109, 1500, 9999
            ]),
        ];
    }
}