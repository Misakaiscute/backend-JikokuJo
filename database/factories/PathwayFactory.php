<?php

namespace Database\Factories;

use App\Models\Pathway;
use Illuminate\Database\Eloquent\Factories\Factory;

class PathwayFactory extends Factory
{
    protected $model = Pathway::class;

    public function definition(): array
    {
        return [
            'id' => (string) $this->faker->unique()->uuid(),
            'mode' => $this->faker->randomElement([1, 2, 3, 4, 5, 6, 7, 8]),
            'is_bidirectional' => $this->faker->boolean(50),
            'from_stop_id' => null,
            'to_stop_id' => null,
            'traversal_time' => $this->faker->numberBetween(30, 600),
        ];
    }
}
