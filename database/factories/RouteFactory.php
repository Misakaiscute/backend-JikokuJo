<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'id' => (string) $this->faker->unique()->bothify('R-###'),
            'agency_id' => null,
            'short_name' => $this->faker->bothify('##'),
            'long_name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement([0, 1, 3, 11, 2, 109, 1500, 9999]),
            'desc' => $this->faker->sentence(5),
            'color' => $this->faker->hexColor(),
            'text_color' => '#000000',
            'sort_order' => 0,
        ];
    }
}
